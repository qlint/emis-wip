$(document).ready(function () {
    //mean analysis
    excel = new ExcelGen({
        "src_id": "test_table",
        "show_header": true
    });
    $("#generate-excel").click(function () {
        excel.generate();
    });
    //mean analysis sent_date

    //grade comparison
    excel = new ExcelGen({
        "src_id": "test_table2",
        "show_header": true
    });
    $("#generate-excel2").click(function () {
        excel.generate();
    });
    //grade comparison end
});
