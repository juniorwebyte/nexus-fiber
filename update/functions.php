<?php
/**
 * Funções auxiliares para o sistema de atualização
 */

function update_log($message) {
    $logFile = dirname(__DIR__) . '/logs/update.log';
    $date = date('Y-m-d H:i:s');
    $msg = "[$date] $message" . PHP_EOL;
    file_put_contents($logFile, $msg, FILE_APPEND);
}

function get_applied_versions($conn) {
    if (!$conn) return [];
    // Garante que a tabela exista antes de consultar
    $conn->query("CREATE TABLE IF NOT EXISTS system_versions (version VARCHAR(50) PRIMARY KEY, applied_at DATETIME DEFAULT CURRENT_TIMESTAMP, log LONGTEXT NULL)");
    
    $versions = [];
    $res = $conn->query("SELECT version FROM system_versions ORDER BY applied_at ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $versions[] = trim($row['version']);
        }
    }
    return $versions;
}

function get_available_updates($current_versions) {
    $updatesPath = __DIR__ . '/';
    $dirs = array_filter(glob($updatesPath . '*'), 'is_dir');
    $available = [];

    foreach ($dirs as $dir) {
        $ver = basename($dir);
        if ($ver === 'backups') continue;
        
        if (version_compare($ver, '0.0.0', '>') && !in_array($ver, $current_versions)) {
            $descFile = $dir . '/description.json';
            $description = is_file($descFile) ? json_decode(file_get_contents($descFile), true) : ['description' => 'Sem descrição'];
            $available[] = [
                'version' => $ver,
                'path' => $dir,
                'info' => $description
            ];
        }
    }

    // Ordenar por versão
    usort($available, function($a, $b) {
        return version_compare($a['version'], $b['version']);
    });

    return $available;
}

function backup_database($host, $user, $pass, $name) {
    $backupDir = __DIR__ . '/backups/';
    $filename = $backupDir . 'db_backup_' . date('Ymd_His') . '.sql';
    
    // Tenta usar mysqldump (padrão XAMPP)
    // No Windows/XAMPP, o binário costuma estar em c:/xampp/mysql/bin/mysqldump.exe
    $mysqlPath = 'mysqldump'; 
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        if (file_exists('C:/xampp/mysql/bin/mysqldump.exe')) {
            $mysqlPath = 'C:/xampp/mysql/bin/mysqldump.exe';
        }
    }

    $command = "$mysqlPath --host=$host --user=$user --password=\"$pass\" $name > \"$filename\" 2>&1";
    @exec($command, $output, $return);
    
    if ($return !== 0) {
        update_log("Backup do banco falhou (Código: $return). Verifique se mysqldump está no PATH.");
        // Fallback: Tenta criar um dump básico via PHP se necessário (implementação futura)
        return false;
    }
    
    update_log("Backup do banco consolidado: " . basename($filename));
    return $filename;
}

function backup_files() {
    $backupDir = __DIR__ . '/backups/';
    $zipName = $backupDir . 'files_backup_' . date('Ymd_His') . '.zip';
    
    if (!class_exists('ZipArchive')) {
        update_log("ZipArchive não disponível. Backup de arquivos cancelado.");
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
        $root = dirname(__DIR__);
        // Pastas críticas
        $folders = ['admin', 'api', 'assets', 'class', 'classes', 'config', 'includes', 'pages', 'gateway'];
        
        foreach ($folders as $folder) {
            $folderPath = $root . '/' . $folder;
            if (is_dir($folderPath)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($folderPath),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($root) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            }
        }
        $zip->close();
        update_log("Backup de arquivos Zip consolidado: " . basename($zipName));
        return $zipName;
    }
    return false;
}
function restore_database($host, $user, $pass, $name, $filename) {
    if (!is_file($filename)) return false;
    
    $mysqlPath = 'mysql';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        if (file_exists('C:/xampp/mysql/bin/mysql.exe')) {
            $mysqlPath = 'C:/xampp/mysql/bin/mysql.exe';
        }
    }

    $command = "$mysqlPath --host=$host --user=$user --password=\"$pass\" $name < \"$filename\" 2>&1";
    @exec($command, $output, $return);
    
    if ($return !== 0) {
        update_log("Erro crítico na restauração do banco: " . implode("\n", $output));
        return false;
    }
    
    update_log("Rollback realizado com sucesso do arquivo: " . basename($filename));
    return true;
}
