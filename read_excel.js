const xlsx = require('xlsx');
const workbook = xlsx.readFile('arquivos/ABAS APP.xlsx');
workbook.SheetNames.forEach(sheetName => {
    console.log(`\n\n=== Sheet: ${sheetName} ===`);
    console.log(xlsx.utils.sheet_to_json(workbook.Sheets[sheetName]));
});
