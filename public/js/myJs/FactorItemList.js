function SaveFactorItemList() {
	$.ajax({
        url: "save_factor_item_list",
        method: "post",
        data: $('#frmSaveFactorItemList').serialize(),
        dataType: "json",
        beforeSend: function(){

        },
        success: function(JsonObject){
            if(JsonObject['result'] == 1){
                Swal({
                    position: 'top-end',
                    type: 'success',
                    title: 'Saved Successfully!',
                    showConfirmButton: false,
                    timer: 1500,
                    customClass: 'swal-wide',
                });
                $('#frmSaveFactorItemList')[0].reset()
            	$("#mdlSaveFactorItemList").modal('hide');
            	dtFactorItemLists.draw();
            }
            else{
            	Swal({
                    position: 'top-end',
                    type: 'error',
                    title: 'Saving Failed!',
                    showConfirmButton: false,
                    timer: 1500,
                    customClass: 'swal-wide',
                });
            }
        },
        error: function(data, xhr, status){
            console.log('Data: ' + data + "\n" + "XHR: " + xhr + "\n" + "Status: " + status);
        }
    });
}

function ActionFactorItemList() {
    $.ajax({
        url: "action_factor_item_list",
        method: "post",
        data: $('#frmActionFactorItemList').serialize(),
        dataType: "json",
        beforeSend: function(){

        },
        success: function(JsonObject){
            if(JsonObject['result'] == 1){
                Swal({
                    position: 'top-end',
                    type: 'success',
                    title: 'Saved Successfully!',
                    showConfirmButton: false,
                    timer: 1500,
                    customClass: 'swal-wide',
                });
                $("#mdlActionFactorItemList").modal('hide');
                $("#frmActionFactorItemList")[0].reset();
                dtFactorItemLists.draw();
            }
            else{
                Swal({
                    position: 'top-end',
                    type: 'error',
                    title: 'Saving Failed!',
                    showConfirmButton: false,
                    timer: 1500,
                    customClass: 'swal-wide',
                });
            }
        },
        error: function(data, xhr, status){
            console.log('Data: ' + data + "\n" + "XHR: " + xhr + "\n" + "Status: " + status);
        }
    });
}

function GetFactorItemListById(factorItemListID){
    $.ajax({
        url: 'get_factor_item_list_by_id',
        method: 'get',
        data: {
            'factor_item_list_id': factorItemListID
        },
        dataType: 'json',
        beforeSend: function(){
            $("input[name='name'", $("#frmSaveFactorItemList")).val('');
        },
        success: function(JsonObject){
            if(JsonObject['data'] != null){
                $("input[name='name'", $("#frmSaveFactorItemList")).val(JsonObject['data']['name']);
            }
            else{
                $("input[name='name'", $("#frmSaveFactorItemList")).val('');
            }
        },
        error: function(data, xhr, status){
            Swal({
                position: 'top-end',
                type: 'error',
                title: 'An error occured!',
                showConfirmButton: false,
                timer: 1500,
                customClass: 'swal-wide',
            });
            $("input[name='name'", $("#frmSaveFactorItemList")).val('');
            console.log('Data: ' + data + "\n" + "XHR: " + xhr + "\n" + "Status: " + status);
        }
    });
}