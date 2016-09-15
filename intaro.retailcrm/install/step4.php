<?php
if (!check_bitrix_sessid())
    return;
IncludeModuleLangFile(__FILE__);
?>

<style type="text/css">
    .instal-load-block { /* */ }
    
    .instal-load-label {
        color: #000;
        margin-bottom: 15px;
    }
    
    .instal-progress-bar-outer {
        height: 32px;
        border:1px solid;
        border-color:#9ba6a8 #b1bbbe #bbc5c9 #b1bbbe;
        -webkit-box-shadow: 1px 1px 0 #fff, inset 0 2px 2px #c0cbce;
        box-shadow: 1px 1px 0 #fff, inset 0 2px 2px #c0cbce;
        background-color:#cdd8da;
        background-image:-webkit-linear-gradient(top, #cdd8da, #c3ced1);
        background-image:-moz-linear-gradient(top, #cdd8da, #c3ced1);
        background-image:-ms-linear-gradient(top, #cdd8da, #c3ced1);
        background-image:-o-linear-gradient(top, #cdd8da, #c3ced1);
        background-image:linear-gradient(top, #ced9db, #c3ced1);
        border-radius: 2px;
        text-align: center;
        color: #6a808e;
        text-shadow: 0 1px rgba(255,255,255,0.85);
        font-size: 18px;
        line-height: 35px;
        font-weight: bold;
    }

    .instal-progress-bar-alignment {
        height: 28px;
        margin: 0;
        position: relative;
    }

    .instal-progress-bar-inner {
        height: 28px;
        border-radius: 2px;
        border-top: solid 1px #52b9df;
        background-color:#2396ce;
        background-image:-webkit-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
        background-image:-moz-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
        background-image:-ms-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
        background-image:-o-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
        background-image:linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
        position: absolute;
        overflow: hidden;
        top: 1px;
        left:0;
    }

    .instal-progress-bar-inner-text {
        color: #fff;
        text-shadow: 0 1px rgba(0,0,0,0.2);
        font-size: 18px;
        line-height: 32px;
        font-weight: bold;
        text-align: center;
        position: absolute;
        left: -2px;
        top: -2px;
    }
</style>

<script type="text/javascript" src="/bitrix/js/main/jquery/jquery-1.7.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() { 
        var globStop = false;
        
        $('#percent').width($('.instal-progress-bar-outer').width());
        
        $(window).resize(function(){ // strechin progress bar
            $('#percent').width($('.instal-progress-bar-outer').width());
        });
        
        // orderUpload function
        function orderUpload(finish) {
            if(globStop)
                return false;
            
            if(finish == 1) {
                $('#status').text('<?php echo GetMessage("MESS_3"); ?>');
                BX.closeWait();
                $('input[name="inst"]').css('opacity', '1').removeAttr('disabled');
                $('input[name="stop"]').css('opacity', '0.5').attr('disabled', 'disabled');
                $('input[name="stop"]').attr('value', '<?php echo GetMessage("START_1"); ?>');
                return true; // exit from function, end recursion
            }
            
            var handlerUrl = $(this).parents('form').attr('action');
            var step       = $('input[name="continue"]').val();
            var id         = $('input[name="id"]').val();
            var install    = $('input[name="install"]').val();
            var sessid     = BX.bitrix_sessid();
            
            var data = 'install=' + install +'&step=' + step + '&sessid=' + sessid +
                        '&id=' + id + '&ajax=1&finish=' + finish;
            
            // ajax request
            $.ajax({
                type: 'POST',
                url: handlerUrl,
                data: data,
                dataType: 'json',
                success: function(response) {
                    $('#indicator').css('width', response.percent + '%');
                    $('#percent').html(response.percent + '%');
                    $('#percent2').html(response.percent + '%');
                    
                    orderUpload(response.finish); // wait until next response
                    
                },
                error: function () {
                    BX.closeWait();
                    $('input[name="inst"]').css('opacity', '1').removeAttr('disabled');
                    $('input[name="stop"]').attr('name', 'start');
                    $('input[name="stop"]').attr('value', '<?php echo GetMessage("START_3"); ?>');
                    $('#status').text('<?php echo GetMessage('MESS_4'); ?>');
                    globStop = true;
                    
                    alert('<?php echo GetMessage('MESS_5'); ?>');
                }
            });
        }
        
        $('input[name="start"]').live('click', function() {  
            BX.showWait();
            
            $(this).attr('name', 'stop');
            $(this).attr('value', '<?php echo GetMessage("START_2"); ?>');
            $('#status').text('<?php echo GetMessage('MESS_2'); ?>');
            
            if(globStop)
                globStop = false;
            
            // hide next step button
            $('input[name="inst"]').css('opacity', '0.5').attr('disabled', 'disabled');
           
            orderUpload(0);
            
            return false;
        });
        
        $('input[name="stop"]').live('click', function() {
            BX.closeWait();
            
            // show next step button
            $('input[name="inst"]').css('opacity', '1').removeAttr('disabled');
            
            $(this).attr('name', 'start');
            $(this).attr('value', '<?php echo GetMessage("START_3"); ?>');
            $('#status').text('<?php echo GetMessage('MESS_4'); ?>');
            globStop = true;
            
            return false;
        });  
        
    });
</script>

<form action="<?php echo $APPLICATION->GetCurPage() ?>" method="POST">
    <?php echo bitrix_sessid_post(); ?>
    <input type="hidden" name="lang" value="<?php echo LANGUAGE_ID ?>">
    <input type="hidden" name="id" value="intaro.retailcrm">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="step" value="5">
    <input type="hidden" name="continue" value="4">
    <div class="adm-detail-content-item-block">
        <table class="adm-detail-content-table edit-table" id="edit1_edit_table">
            <tbody>
                <tr class="heading">
                    <td colspan="2"><b><?php echo GetMessage('STEP_NAME'); ?></b></td>
                </tr>
            </tbody>
        </table>
        <div class="instal-load-block" id="result">
            <div class="instal-load-label" id="status"><?php echo GetMessage('MESS_1'); ?></div>
            <div class="instal-progress-bar-outer">
                <div class="instal-progress-bar-alignment" style="width: 100%;">
                    <div class="instal-progress-bar-inner" id="indicator" style="width: 0%;">
                        <div class="instal-progress-bar-inner-text" style="width: 100%;" id="percent">0%</div>
                    </div>
                    <span id="percent2">0%</span>
                </div>
            </div>
        </div>
    <br />
    <div style="padding: 1px 13px 2px; height:28px;">
        <div align="right" style="float:right; width:50%; position:relative;">
            <input type="submit" name="inst" value="<?php echo GetMessage("MOD_NEXT_STEP"); ?>" class="adm-btn-save">
        </div>
        <div align="left" style="float:right; width:50%; position:relative; visible: none;">
            <input type="submit" name="start" value="<?php echo GetMessage("START_1"); ?>" class="adm-btn-save">
        </div>
    </div>
    </div>
</form>