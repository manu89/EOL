/**
 * Created by michele on 29/11/15.
 */

var checkedass=false;
var checkedtopic=false;
var checkedgraphic=false;

/***********************
 * @name selectAllAssesment
 * @desc Select or deselect all checkboxes relative to assesment information
 **********************/
function selectAllAssesment(){
    if(!checkedass){
        $(".checkass").each(function(){
            $(this).prop("checked",true);
            checkedass=true;
            //console.log(checked);
            $("#selectall").text(ttDeselectAll);
        });
    }
    else{
        $(".checkass").each(function(){
            $(this).prop("checked",false);
            checkedass=false;
            //console.log(checked);
            $("#selectall").text(ttSelectAll);
        });
    }
}

/***********************
 * @name selectAllTopic
 * @desc Select or deselect all checkboxes relative to topic information
 **********************/
function selectAllTopic(){
    if(!checkedtopic){
        $(".checktopic").each(function(){
            $(this).prop("checked",true);
            checkedtopic=true;
            //console.log(checked);
            $("#selectallt").text(ttDeselectAll);
        });
    }
    else{
        $(".checktopic").each(function(){
            $(this).prop("checked",false);
            checkedtopic=false;
            //console.log(checked);
            $("#selectallt").text(ttSelectAll);
        });
    }
}

/***********************
 * @name selectAllGraphic
 * @desc Select or deselect all checkboxes relative to graphical displays
 **********************/
function selectAllGraphic(){
    if(!checkedgraphic){
        $(".checkgraphic").each(function(){
            $(this).prop("checked",true);
            checkedgraphic=true;
            //console.log(checked);
            $("#selectallg").text(ttDeselectAll);
        });
    }
    else{
        $(".checkgraphic").each(function(){
            $(this).prop("checked",false);
            checkedgraphic=false;
            //console.log(checked);
            $("#selectallg").text(ttSelectAll);
        });
    }
}