/**
 * Created by michele on 22/10/15.
 */
var exams=new Array(100);//array of selected exams
var user; //variable used for userid
var paramuser; //variable that contain the user id or email want to show on the report
var groups=new Array(100);//array of selected groups
var minscore;//variable that contain the minimal score of the exam to show
var maxscore;//variable that contain the maximum score of the exam to show

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
    var trovato=false;
    for (v=0; v < exams.length; v++) {
        if (exams[v] == exam) {
            trovato = true;
        }
    }
    if (!trovato){
        for (v=0; v < exams.length; v++){
            if (exams[v]==null){
                exams[v]=exam;
                $("#selected").append("<option id="+exam+" value="+exam+">"+exam+"</option>");
                break;
            }
        }
    }
        //y=i;

    /*if (x==0){
        exams[y]=exam;
        $("#selected").append("<option id="+exam+" value="+exam+">"+exam+"</option>");
        y++;
    }*/
    for (t=0; t< exams.length; t++){
     console.log(exams[t]);
     }

}

/**
 *  @name   clearAssesments
 *  @descr  Empty selected assesment form
 */
function clearAssessments(){
   // for (i = 0; i < exams.length; i++){
            //exams[i]=null;
    exams=new Array(100);
    //}
   // y=0;
    $("#selected").html("");

    for (t=0; t< exams.length; t++) {
        console.log(exams[t]);
    }
}

/**
 *  @name   removeAssesments
 *  @descr  Delete specific selected assessment
 */
function removeAssessment(exam){
    var x=0;
    if(exams[exams.length]==exam){
        exams[exams.length]=null;
        $("#"+exam).remove();
        x=1;
    }
    for (i = 0; i < exams.length-1; i++){
        if (exams[i]==exam){
            for (r=i; r < exams.length; r++){
                exams[r]=exams[r+1];
            }
        }
    }
    if (x==0){
        exams[i]=null;
        $("#"+exam).remove();
    }


   for (t=0; t< exams.length; t++) {
       console.log(exams[t]);
   }

}

/**
 *  @name   showPartecipant
 *  @descr  Shows lightbox of partecipants
 */
function showPartecipant(){
   $.ajax({
        url     : "index.php?page=report/showpartecipant",
        type    : "post",
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

function unlock(el,el1,el2){
    if(el.checked){
        el1.disabled = false;
        el2.disabled = false;
    }
    else{
        el1.disabled = "disabled";
        el2.disabled = "disabled";
    }

}

/**
 *  @name   printStudent
 *  @descr  Shows partecipants in the select form of lightbox
 */
function printStudent(){
    /*for (t=0; t< exams.length; t++){
        console.log(exams[t]);
    }*/
    $.ajax({
        url     : "index.php?page=report/showstudent",
        type    : "post",
        data : {
            exams : JSON.stringify(exams)
        },
        success : function (data){
            $("#searchedstud").html(data);
        },
        error : function (request, status, error) {
            alert("jQuery AJAX request error:".error);
        }
    });
}

/**
 *  @name   addStudent
 *  @descr  Show selected student in the textarea of main page
 */
function addStudent(iduser){
    $.ajax({
        url     : "index.php?page=report/addstudent",
        type    : "post",
        data    : {
            iduser: iduser
        },
        success : function (data){
                user=iduser;
                $("#student").html(data);
                closeLightbox($('#partecipants'));

        },
        error : function (request, status, error) {
           alert("jQuery AJAX request error:".error);
        }
    });
}

/**
 *  @name   removePartecipant
 *  @descr  Remove the selected student from the textarea
 */
function removePartecipant(iduser){
    $("#student").html("");
}


/**
 *  @name   showParticipantDetails
 *  @descr  Shows lightbox of partecipants
 */
function showParticipantDetails(){
    $.ajax({
        url     : "index.php?page=report/showparticipantdetails",
        type    : "post",
        success : function (data){
            $("body").append(data);
            newLightbox($("#participantsdetails"), {});
        },
        error : function (request, status, error) {
            alert("jQuery AJAX request error:".error);
        }
    });
}

/**
 *@name closePartecipantDetails
 *@descr Close lightbox of partecipant details
 */
function closePartecipantDetails(){
    closeLightbox($('#participantsdetails'));
}

/**
 *  @name   printStudentDetail
 *  @descr  Show students details in the select form of showparticipantdetail ligthbox
 */
function printStudentDetail(){
    $.ajax({
        url     : "index.php?page=report/printparticipantdetails",
        type    : "post",
        data    : {
            iduser : user
        },
        success : function (data){
            $("#detail").html(data);

        },
        error : function (request, status, error) {
            alert("jQuery AJAX request error:".error);
        }
    });
}

/**
 *  @name   addStudentDetail
 *  @descr  Show students detail in the relative textarea
 */
function addStudentDetail(param){
            paramuser=param;
            $("#studentDetail").html(param);
            closeLightbox($('#participantsdetails'));
}


/**
 *  @name   removePartecipantDetail
 *  @descr  Remove the detail of selected student from the textarea
 */
function removePartecipantDetail(){
    $("#studentDetail").html("");
}

/**
 *  @name   transferData
 *  @descr  transfer all filter parameters to the report template
 */
function transferData(min,max){
    minscore=min;
    maxscore=max;
    $.ajax({
        url     : "index.php?page=report/aoreportparameters",
        type    : "post",
        data    : {
            iduser : paramuser,
            exams : JSON.stringify(exams),
            minscore: minscore,
            maxscore: maxscore,
            groups: JSON.stringify(groups)
        },
        success : function (data){
            window.location.assign("index.php?page=report/aoreporttemplate")
        },
        error : function (request, status, error) {
            alert("jQuery AJAX request error:".error);
        }
    });
}