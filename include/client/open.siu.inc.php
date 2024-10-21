<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');
$info=array();
if($thisclient && $thisclient->isValid()) {
    $info=array('name'=>$thisclient->getName(),
                'email'=>$thisclient->getEmail(),
                'phone'=>$thisclient->getPhoneNumber());
}

$info=($_POST && $errors)?Format::htmlchars($_POST):$info;

$form = null;
if (!$info['topicId']) {
    if (array_key_exists('topicId',$_GET) && preg_match('/^\d+$/',$_GET['topicId']) && Topic::lookup($_GET['topicId']))
        $info['topicId'] = intval($_GET['topicId']);
    else
        $info['topicId'] = $cfg->getDefaultTopicId();
}

$forms = array();
if ($info['topicId'] && ($topic=Topic::lookup($info['topicId']))) {
    foreach ($topic->getForms() as $F) {
        if (!$F->hasAnyVisibleFields())
            continue;
        if ($_POST) {
            $F = $F->instanciate();
            $F->isValidForClient();
        }
        $forms[] = $F->getForm();
    }
}

?>
<h2>Crear Nueva Solicitud (Ticket)</h2>
<!-- p><?php echo __('Please fill in the form below to open a new ticket.');?></p -->


<form id="ticketForm" method="post" action="siu.php" enctype="multipart/form-data">
  <?php csrf_token(); ?>
  <input type="hidden" name="a" value="open">
  
  <nav  id="myTab" role="tablist">
	<div class="nav nav-tabs" id="nav-tab" role="tablist">
		<button class="nav-link " id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">1. <i class="icon-user"></i> Información de Contacto</button>
		<button class="nav-link active" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">2. <i class="icon-ticket"></i> Datos de la Solicitud</button>
    <!-- button class="nav-link" id="nav-contact-tab" data-bs-toggle="tab" data-bs-target="#nav-contact" type="button" role="tab" aria-controls="nav-contact" aria-selected="false">Contact</button -->
    
	</div>
</nav>

<div class="tab-content" id="nav-tabContent">
  <div class="tab-pane fade mx-2" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab" tabindex="0">
    <table width="100%" cellpadding="1" cellspacing="0" border="0">
    <tbody>
<?php
        if (!$thisclient) {
			if(count($_GET)){
				$uform = UserForm::getUserForm()->getForm($_GET);			
				if ($_GET) $uform->isValid();		
			}else{
				$uform = UserForm::getUserForm()->getForm($_POST);			
				if ($_POST) $uform->isValid();		
			}
            
            $uform->render(array('staff' => false, 'mode' => 'create'));
			
        } 
        else { ?>
            <tr><td colspan="2"><hr /></td></tr>
        <tr><td><?php echo __('Email'); ?>:</td><td><?php
            echo $thisclient->getEmail(); ?></td></tr>
        <tr><td><?php echo __('Client'); ?>:</td><td><?php
            echo Format::htmlchars($thisclient->getName()); ?></td></tr>
        <?php } ?>
    </tbody>
	</table>
	<div class="d-flex justify-content-end">
		<button type="button" class="btn btn-light me-2 mt-2" name="cancel" value="" onclick=""><?php echo __('Cancel'); ?></button>
		<button type="button" id="next-1" class="btn btn-success me-2 mt-2" name="continuar" value="">Continuar >></button>
	</div>
  </div>
  <div class="tab-pane mx-2 fade show active" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
  	<table width="100%" cellpadding="1" cellspacing="0" border="0">
    <tbody id="topic_body">
    <tr><td colspan="2">
        <div class="form-header" style="margin-bottom:0.5em">
        <b><?php echo __('Help Topic'); ?>  <font class="error">*</font></b>
        </div>
    </td></tr>
    <tr>
        <td colspan="2">
            <select id="topicId" name="topicId" onchange="javascript:
                    var data = $(':input[name]', '#dynamic-form').serialize();
                    $.ajax(
                      'ajax.php/form/help-topic/' + this.value,
                      {
                        data: data,
                        dataType: 'json',
                        success: function(json) {
                          $('#dynamic-form').empty().append(json.html);
                          $(document.head).append(json.media);
                        }
                      });">
                <option value="" selected="selected">&mdash; <?php echo __('Select a Help Topic');?> &mdash;</option>
                <?php
                if($topics=Topic::getPublicHelpTopics()) {
                    foreach($topics as $id =>$name) {
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $id, ($info['topicId']==$id)?'selected="selected"':'', $name);
                    }
                } ?>
            </select>
            <font class="error"><?php echo $errors['topicId']; ?></font>
        </td>
    </tr>
    </tbody>
    <tbody id="dynamic-form">
        <?php
        $options = array('mode' => 'create');
        foreach ($forms as $form) {
            include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
        } ?>
    </tbody>
    <tbody>
    <?php
    if($cfg && $cfg->isCaptchaEnabled() && (!$thisclient || !$thisclient->isValid())) {
        if($_POST && $errors && !$errors['captcha'])
            $errors['captcha']=__('Please re-enter the text again');
        ?>
    <tr class="captchaRow">
        <td class="required"><?php echo __('CAPTCHA Text');?>:</td>
        <td>
            <span class="captcha"><img src="captcha.php" border="0" align="left"></span>
            &nbsp;&nbsp;
            <input id="captcha" type="text" name="captcha" size="6" autocomplete="off">
            <em><?php echo __('Enter the text shown on the image.');?></em>
            <font class="error">*&nbsp;<?php echo $errors['captcha']; ?></font>
        </td>
    </tr>
    <?php
    } ?>
    <tr><td colspan=2>&nbsp;</td></tr>
    </tbody>
	</table>
	<hr/>
	  <div class="d-flex justify-content-end">
			<button type="button" class="btn btn-light me-2" name="cancel" value="<?php echo __('Cancel'); ?>" onclick=""><?php echo __('Cancel'); ?></button>
			<button type="button" id="back-1" class="btn btn-success me-2" value=""><< Regresar</button>
			<!-- button type="reset" class="btn btn-secondary me-2" name="reset" value="<?php echo __('Reset');?>"><?php echo __('Reset');?></button -->
			<button type="submit" class="btn btn-primary" value="<?php echo __('Create Ticket');?>"><?php echo __('Create Ticket');?></button>
	  </div>
  </div>
  <!-- div class="tab-pane fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab" tabindex="0">...</div -->
</div>




</form>

<script>
$(function(){
	if( ( new URLSearchParams(window.location.search) ).has('topicId') || $("#topicId").val() != '' ){
		$("#topic_body").hide();
	}
	$('h3:contains("Caracterización del Servicio")').hide();
	
	$("#back-1").on('click', function(){
		$(document.querySelector('#myTab button[data-bs-target="#nav-home"]')).click();
	});
	
	$("#next-1").on('click', function(){
		
			$(document.querySelector('#myTab button[data-bs-target="#nav-profile"]')).click()
								
	});
	
	
	if($("#nav-home div.error").get().length){
		$(document.querySelector('#myTab button[data-bs-target="#nav-home"]')).click()
	}
		
	
})
</script>
