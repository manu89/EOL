/**
 * File: Showstudentslist.js
 * User: Masterplan
 * Date: 08/07/14
 * Time: 19:58
 * Desc: Show students registered to requeste exam
 */

var testRowEdit = null;

var registrationsTable = null;
var rtci = {
    status : 0,
    name : 1,
    email : 2,
    timeStart : 3,
    timeEnd : 4,
    timeUsed : 5,
    scoreTest : 6,
    scoreFinal : 7,
    manage : 8,
    studentID : 9,
    testID : 10
};

$(function(){

    $("#addStudents").on("click", showAddStudentsPanel);

    registrationsTable = $("#registrationsTable").DataTable({
        scrollY:        200,
        scrollCollapse: false,
        jQueryUI:       true,
        paging:         false,
        order: [rtci.name, "asc"],
        columns : [
            { className: "uStatus", searchable : false, type: "alt-string", width : "10px" },
            { className: "uName" },
            { className: "uEmail" },
            { className: "uTimeStart"},
            { className: "uTimeEnd"},
            { className: "uTimeUsed"},
            { className: "uScoreTest"},
            { className: "uScoreFinal"},
            { className: "uManage", width : "30px" , searchable : false, sortable : false },
            { className: "uStudentID", visible : false },
            { className: "uTestID", visible : false }
        ],
        language : {
            info: ttDTRegisteredStudentInfo,
            infoFiltered: ttDTRegisteredStudentFiltered,
            infoEmpty: ttDTRegisteredStudentEmpty
        }
    });
    $("#registrationsTable_filter").css("margin-right", "50px")
                                   .after($("#addStudents").parent())
                                   .before($("#registrationsTable_info"));

});

/**
 *  @name   toggleBlackTest
 *  @param  askConfirmationAndSelectedTest          Array       askConfirmation Boolean, img of test to block/unblock
 *  @descr  Block/Unblock single test
 */
function toggleBlockTest(askConfirmationAndSelectedTest){
    testRowEdit = $(askConfirmationAndSelectedTest[1]).closest("tr");
    var confirm = ttCBlockTest;
    if($(askConfirmationAndSelectedTest[1]).closest("span").hasClass("unblock"))
        confirm = ttCUnblockTest;
    if((!askConfirmationAndSelectedTest[0]) || (confirmDialog(ttWarning, confirm, toggleBlockTest, new Array(false, askConfirmationAndSelectedTest[1])))){
        var idTest = registrationsTable.row(testRowEdit).data()[rtci.testID];
        $.ajax({
            url     : "index.php?page=exam/toggleblock",
            type    : "post",
            data    : {
                idTest    :     idTest
            },
            success : function (data){
                data = data.split(ajaxSeparator);
                if(data[0] == "ACK"){
                    var rowIndex = registrationsTable.row(testRowEdit).index();
                    if($(askConfirmationAndSelectedTest[1]).closest("span").hasClass("block")){
                        showSuccessMessage(ttMTestBlocked);
                        registrationsTable.cell(rowIndex, rtci.status).data('<img src="'+imageDir+'blocked.png" title="'+ttBlocked+'"/>');
                        registrationsTable.cell(rowIndex, rtci.manage).data(
                            '<span class="manageButton unblock">'+
                            '    <img src="'+imageDir+'unblock.png" onclick="toggleBlockTest(new Array(true, this));" title="'+ttUnblock+'">'+
                            '</span>');
                    }else{
                        showSuccessMessage(ttMTestUnblocked);
                        if(data[1] == 'w'){         // Now the test is waiting
                            registrationsTable.cell(rowIndex, rtci.status).data('<img src="'+imageDir+'Waiting.png" title="'+ttWaiting+'"/>');
                            registrationsTable.cell(rowIndex, rtci.manage).data(
                                '<span class="manageButton block">'+
                                '    <img src="'+imageDir+'block.png" onclick="toggleBlockTest(new Array(true, this));" title="'+ttBlock+'">'+
                                '</span>');

                        }else{                      // Now the test is started
                            registrationsTable.cell(rowIndex, rtci.status).data('<img src="'+imageDir+'Started.png" title="'+ttStarted+'"/>');
                            registrationsTable.cell(rowIndex, rtci.manage).data(
                                '<span class="manageButton block">'+
                                '    <img src="'+imageDir+'block.png" onclick="toggleBlockTest(new Array(true, this));" title="'+ttBlock+'">'+
                                '</span>');
                        }
                    }
                }else{
                    showErrorMessage(data);
                }
            },
            error : function (request, status, error) {
                alert("jQuery AJAX request error:".error);
            }
        });
    }
}

/**
 *  @name   showAddStudentsPanel
 *  @descr  Shows students table to add new registrations
 */
function showAddStudentsPanel(){
    $.ajax({
        url     : "index.php?page=exam/showaddstudentspanel",
        type    : "post",
        data    : {
            idExam  :   $("#idExam").val()
        },
        success : function (data){
            if(data == "NACK"){
                alert(data);
            }else{
//                alert(data);
                $("body").append(data);
                newLightbox($("#addStudentsPanel"), {});
            }
        },
        error : function (request, status, error) {
            alert("jQuery AJAX request error:".error);
        }
    });
}

/**
 *  @name   refreshStudentsList
 *  @descr  Refreshes requested exam's students list
 */
function refreshStudentsList(){
    var idExam = examsTable.row(examRowEdit).data()[etci.examID];
    $.ajax({
        url     : "index.php?page=exam/showregistrationslist",
        type    : "post",
        data    : {
            idExam  :  idExam,
            action  :  "refresh"
        },
        success : function (data){
            if(data == "NACK"){
//                alert(data);
            }else{
//                alert(data);
                $("#registrationsList .boxContent").html(data);
            }
        },
        error : function (request, status, error) {
            alert("jQuery AJAX request error:".error);
        }
    });

    //rivisualizzo pulsante export
    $("#exportbutton").removeClass("hidden");
    $("#exportbutton").addClass("button");
}

/**
 *  @name   correctTest
 *  @param  selected        DOM Element         <img> of requested test
 *  @descr  Shows page to correct requested test
 */
function correctTest(selected){
    var idTest = registrationsTable.row($(selected).closest("tr")).data()[rtci.testID];
    $("#idTest").val(idTest);
    $("#idTestForm").attr("action", "index.php?page=exam/correct").submit();
}

/**
 *  @name   viewTest
 *  @param  selected        DOM Element         <img> of requested test
 *  @descr  Shows page to view requested test
 */
function viewTest(selected){
    var idTest = registrationsTable.row($(selected).closest("tr")).data()[rtci.testID];
    $("#idTest").val(idTest);
    $("#idTestForm").attr("target", "_blank").attr("action", "index.php?page=exam/view").submit();
}

/**
 *  @name   closeStudentsList
 *  @descr  Closes registrations list panel
 */
function closeStudentsList(){
    closeLightbox($("#registrationsList"));
}

/**
 *  @name   exportPDF
 *  @descr  send data to generate a pdf of students registered to the exam
 */
function exportPDF(){
    var idExam = examsTable.row(examRowEdit).data()[etci.examID];
    var tests= new Array();
    var i=0;
    $("#registrationsTable tbody tr").each(function(){
        tests[i]={
            sName:$(this).find(".sName").text(),
            sEmail:$(this).find(".sEmail").text(),
            sTime:$(this).find(".sTime").text(),
            sScoreFinal:$(this).find(".sScoreFinal").text()
        };
        i++;
    });

    $.ajax({
        url     : "index.php?page=exam/exportdata",
        type    : "post",
        data    : {
            tests: JSON.stringify(tests),
            idExam:idExam,
            sName: $("input[type=checkbox][name=sName]:checked").val(),
            sEmail: $("input[type=checkbox][name=sEmail]:checked").val(),
            sTime: $("input[type=checkbox][name=sTime]:checked").val(),
            sScoreFinal: $("input[type=checkbox][name=sScoreFinal]:checked").val()
        },
        success : function (data){
            window.location.assign("index.php?page=exam/exportpdf")
            //window.open('index.php?page=exam/exportpdf','_blank');
        },
        error : function (request, status, error) {
            alert("jQuery AJAX request error:".error);
        }
    });
}

/**
 *  @name   exportCSV
 *  @descr  send data to generate a csv file of students registered to the exam
 */
function exportCSV(){
    var idExam = examsTable.row(examRowEdit).data()[etci.examID];
    var tests= new Array();
    var i=0;
    $("#registrationsTable tbody tr").each(function(){
        tests[i]={
            sName:$(this).find(".sName").text(),
            sEmail:$(this).find(".sEmail").text(),
            sTime:$(this).find(".sTime").text(),
            sScoreFinal:$(this).find(".sScoreFinal").text()
        };
        i++;
    });

    $.ajax({
        url     : "index.php?page=exam/exportdata",
        type    : "post",
        data    : {
            tests:JSON.stringify(tests),
            idExam:idExam,
            sName: $("input[type=checkbox][name=sName]:checked").val(),
            sEmail: $("input[type=checkbox][name=sEmail]:checked").val(),
            sTime: $("input[type=checkbox][name=sTime]:checked").val(),
            sScoreFinal: $("input[type=checkbox][name=sScoreFinal]:checked").val()
        },
        success : function (data){
            window.location.assign("index.php?page=exam/exportcsv")
        },
        error : function (request, status, error) {
            alert("jQuery AJAX request error:".error);
        }
    });
}

/**
 *  @name   showCheckbox
 *  @descr  show div for selected specific field to export
 */
function showCheckbox(){
    $("#export").removeClass("hidden");
    $("#exportbutton").addClass("hidden");
    $("#exportbutton").removeClass("button");
    $("#csv").removeClass("hidden");
    $("#csv").addClass("button");
    $("#pdf").removeClass("hidden");
    $("#pdf").addClass("button");
}

/***********************
 * @name selectAllFields
 * @desc Select or deselect all checkboxes relative to export data
 **********************/
var checkedfields=false;

function selectAllFields(){
    if(!checkedfields){
        $(".field").each(function(){
            $(this).prop("checked",true);
            checkedfields=true;
            $("#selectallck").text(ttDeselectAll);
        });
    }
    else{
        $(".field").each(function(){
            $(this).prop("checked",false);
            checkedfields=false;
            $("#selectallck").text(ttSelectAll);
        });
    }
}
