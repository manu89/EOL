/**
 * File: Profile.js
 * User: Masterplan
 * Date: 5/30/13
 * Time: 4:49 PM
 * Desc: Shows profile page of user's account and updates user's information
 */

/**
 *  @name   saveProfile
 *  @descr  Save user's informations
 */
function errorEmail(){
    var idquestion = $("#idquestion").val().trim();
    var notes= $("#notes").val().trim();
    alert(idquestion);
    alert(notes);

                $.ajax({
                    url     : "index.php?page=admin/erroremail",
                    type    : "post",
                    data    : {
                        idquestion         :   idquestion,
                        notes        :   notes
                                        },
                    success : function (data, status) {
                        if(data == "ACK"){
                            //alert(data);
                            showSuccessMessage(ttMEdit);
                            setTimeout(function(){location.href = "index.php"}, 2000);
                        }else{
                            //alert(data);
                            showErrorMessage(data);
                        }
                    },
                    error : function (request, status, error) {
                        alert("jQuery AJAX request error:".error);
                    }
                });

}