/**
 * Created by michele on 19/12/15.
 */
/**
 *  @name   printParticipant
 *  @descr  Shows partecipants in the select form
 */
$(document).ready(function(){
    $.ajax({
        url     : "index.php?page=report/showtestscreport",
        type    : "post",
        data : {

        },
        success : function (data){
            $("#crtests").append(data);
        },
        error : function (request, status, error) {
            alert("jQuery AJAX request error:".error);
        }
    });
});