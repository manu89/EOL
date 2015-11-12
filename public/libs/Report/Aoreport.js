/**
 * Created by michele on 22/10/15.
 */

var exams=[];//array of selected exams
var y=0; //variable used for count selected exams

/*************************************
 * Tabs function**
 * This function manage css for active tabs and show or hide the div selected
 * ***********************************
 */
$(document).ready(function(){
    $("#tab1").click(function(){
        $("#partecipantstab").hide();
        $("#groupstab").hide();
        $("#maintab").show();
        $("#t1").addClass("active");
        $("#t3, #t2").removeClass("active");
    });
    $("#tab2").click(function(){
        $("#partecipantstab").hide();
        $("#maintab").hide();
        $("#groupstab").show();
        $("#t1, #t3").removeClass("active");
        $("#t2").addClass("active");
    });
    $("#tab3").click(function(){
        $("#partecipantstab").show();
        $("#groupstab").hide();
        $("#maintab").hide();
        $("#t3").addClass("active");
        $("#t1, #t2").removeClass("active");
    });
});

/**
 * nextMainTab
 * move on the next tab from maintab
 */
function nextMainTab(){
    $("#groupstab").show();
    $("#maintab").hide();
    $("#t1, #t3").removeClass("active");
    $("#t2").addClass("active");
}

/**
 * nextGroupTab
 * move on the next tab from maintab
 */
function nextGroupTab(){
    $("#partecipantstab").show();
    $("#maintab, #groupstab").hide();
    $("#t1, #t2").removeClass("active");
    $("#t3").addClass("active");
}

/**
 * prevGroupTab
 * move on the next tab from maintab
 */
function prevGroupTab(){
    $("#maintab").show();
    $("#partecipantstab, #groupstab").hide();
    $("#t3, #t2").removeClass("active");
    $("#t1").addClass("active");
}


/**
 * prevPartecipantsTab
 * move on the next tab from maintab
 */
function prevPartecipantsTab(){
    $("#groupstab").show();
    $("#partecipantstab, #maintab").hide();
    $("#t3, #t1").removeClass("active");
    $("#t2").addClass("active");
}


/**
 *  @name   printAssesments
 *  @descr  Shows exams in the select form of search
 */
function printAssesments(letter){
    $.ajax({
        url     : "index.php?page=report/showassesments",
        type    : "post",
        data    : {
            letter : letter
        },
        success : function (data){
            $("#searched").html(data);
        },
        error : function (request, status, error) {
            alert("jQuery AJAX request error:".error);
        }
    });
}

/**
 *  @name   addAssesments
 *  @descr  Select assesments
 */
function addAssessment(exam){
    var x=0;
    for (i = 0; i < exams.length; i++){
        if (exams[i]==exam){
            x=1;
        }
        y=i;
    }
    if (x==0){
        exams[y]=exam;
        $("#selected").append("<option id="+exam+" value="+exam+">"+exam+"</option>");
        y++;
    }
   /* for (t=0; t< exams.length; t++){
        console.log(exams[t]);
    }*/

}

/**
 *  @name   clearAssesments
 *  @descr  Empty selected assesment form
 */
function clearAssessments(){
    for (i = 0; i < exams.length; i++){
            exams[i]="";
    }
    y=0;
    $("#selected").html("");

}

/**
 *  @name   removeAssesments
 *  @descr  Delete specific selected assessment
 */
function removeAssessment(exam){
    for (i = 0; i < exams.length; i++){
        if (exams[i]==exam){
            for (r=i; r < exams.length; r++){
                exams[r]=exams[r+1];
            }
        }
    }
    exams[i]="";
    $("#"+exam).remove();
   /* for (t=0; t< exams.length; t++){
        console.log(exams[t]);
    }*/

}

/**
 *  @name   showPartecipant
 *  @descr  Shows lightbox of partecipants
 */
function showPartecipant(){
   $.ajax({
        url     : "index.php?page=report/showpartecipant",
        type    : "post",
        data:{
            action:"show"
        },
        success : function (data){

                $("body").append(data);
                newLightbox($("#partecipants"), {});
        },
        error : function (request, status, error) {
            alert("jQuery AJAX request error:".error);
        }
    });
}

/**
 *@name closePartecipant
 *@descr Close lightbox of partecipants
 */
function closePartecipant(){
    closeLightbox($('#partecipants'));
}