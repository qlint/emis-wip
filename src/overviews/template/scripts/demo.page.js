$(document).ready(function () {
    //mean analysis

    $("#generate-excel").click(function () {
      excel = new ExcelGen({
          "src_id": "test_table",
          "show_header": true
      });

        excel.generate();
    });
    //mean analysis sent_date

    //grade comparison

    $("#generate-excel2").click(function () {
      excel = new ExcelGen({
          "src_id": "test_table2",
          "show_header": true
      });

        excel.generate();
    });
    //grade comparison end.

    //deviations

    $("#generate-excel3").click(function () {
      excel = new ExcelGen({
          "src_id": "test_table3",
          "show_header": true
      });

        excel.generate();
    });
    //deviations end.

    //marks count

    $("#generate-excel4").click(function () {
      excel = new ExcelGen({
          "src_id": "test_table4",
          "show_header": true
      });

        excel.generate();
    });
    //marks count end.
});
