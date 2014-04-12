<?php

// Comprobamos si la constante IN_MYBB existe. Si no existe, paramos la ejecución del archivo mostrando un mensaje.

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


// Enlazamos la función al hook. Esta función acepta varios parámetros, pero solo estos dos son requeridos:
// 1) El nombre del hook.
// 2) El nombre de la función.

$plugins->add_hook("parse_message", "myspoiler_run");

function myspoiler_info()
{
	// Creamos el enlace hacia la hoja de estilo.
	
	global $mybb, $db;
	$editar_estilo = '';
	$query = $db->simple_select('themestylesheets', '*', "name='spoiler.css'");
	$query_tid = $db->write_query("SELECT tid FROM ".TABLE_PREFIX."themes WHERE def='1'");
	$themetid = $db->fetch_array($query_tid);
	if (count($db->fetch_array($query)))
	{
		$editar_estilo = '(<a href="index.php?module=style-themes&action=edit_stylesheet&tid='.$themetid['tid'].'&file=spoiler.css&mode=advanced">Editar la hoja de estilo</a>)';
	}

	// Añadimos la información del plugin.
	
	return array(
		"name"				=> "MyCode: MySpoiler", // Nombre del plugin.
		"description"		=> "It allows to insert the spoiler´s function in messages. {$editar_estilo}", // Descripción del plugin.
		"website"			=> "http://www.soportemybb.com/tema_Plugin-MySpoiler-Permite-insertar-la-función-spoiler-en-los-mensajes", // Sitio web del plugin.
		"author"			=> "<b>abdonroda</b></a> for <a href=\"http://www.soportemybb.com\"><b>SoporteMyBB.com</b></a>", // Autor del plugin.
		"authorsite"		=> "http://www.soportemybb.com/", // Sitio web del autor.
		"version"			=> "1.2", // Versión del plugin.
		"guid"				=> "0bc3ccce40451eb1deb187a31ee7432d", // ID del plugin de MyBB.
		"compatibility"		=> "14*,16*", // Compatibilidad del plugin.
	);
}


function myspoiler_activate()
{
	// Creamos la hoja de estilo para el spoiler.

	global $db;
	$query_tid = $db->write_query("SELECT tid FROM ".TABLE_PREFIX."themes WHERE def='1'");
	$themetid = $db->fetch_array($query_tid);
	$estilo = array(
			'name'         => 'spoiler.css',
			'tid'          => $themetid['tid'],
			'attachedto'   => 'showthread.php|newthread.php|newreply.php|editpost.php|private.php|announcements.php',
			'stylesheet'   => '.spoiler {
	background: #f5f5f5;
	border: 1px solid #bbb;
	margin-bottom: 15px;
	border-radius: 5px;
	-moz-border-radius:5px;
	-webkit-border-radius:5px;
}
span.button {
	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #f9f9f9), color-stop(1, #e9e9e9) );
	background:-moz-linear-gradient( center top, #f9f9f9 5%, #e9e9e9 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr="#f9f9f9", endColorstr="#e9e9e9");
	background-color:#f9f9f9;
	-moz-border-radius:4px;
	-webkit-border-radius:4px;
	border-radius:4px;
	border:1px solid #dcdcdc;
	display:inline-block;
	color:#000;
	font-family:arial;
	font-size:10px;
	font-weight:bold;
	padding: .2em 1em .30em;
	text-decoration:none;
	text-shadow:1px 1px 0px #ffffff;
	box-shadow:inset 0px 1px 0px 0px #ffffff;
	-moz-box-shadow:inset 0px 1px 0px 0px #ffffff;
	-webkit-box-shadow:inset 0px 1px 0px 0px #ffffff;
	margin-top: -3px;
	cursor: pointer;
}
.spoiler_cabecera {
	border-bottom: 1px solid #bbb;
	margin: 0;
	margin-bottom: 4px;
	background: #ddd;
	padding: 4px 5px;
}

.spoiler_contenido {
	padding: 5px;
	height: auto;
	display: block;
	background: #f5f5f5;
}',
			'lastmodified' => TIME_NOW
		);
		$sid = $db->insert_query('themestylesheets', $estilo);
		$db->update_query('themestylesheets', array('cachefile' => "css.php?stylesheet={$sid}"), "sid='{$sid}'", 1);
		$query = $db->simple_select('themes', 'tid');
		while($theme = $db->fetch_array($query))
		{
			require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
			update_theme_stylesheet_list($theme['tid']);
		}
}


function myspoiler_deactivate()
{
	// Borramos la hoja de estilo del spoiler.

	global $db;
	$db->delete_query('themestylesheets', "name='spoiler.css'");
		$query = $db->simple_select('themes', 'tid');
		while($theme = $db->fetch_array($query))
		{
			require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
			update_theme_stylesheet_list($theme['tid']);
		}
}


function myspoiler_run(&$message)
{
	global $db, $lang, $mybb;
    $lang->load("my_spoiler", false, true);
	
	// Creamos el MyCode simple: [spoiler]contenido aquí[/spoiler]

	while(preg_match('#\[spoiler\](.*?)\[\/spoiler\]#si',$message))
	$message = preg_replace('#\[spoiler\](.*?)\[\/spoiler\]#si','<div class="spoiler">
		<div class="spoiler_cabecera"><span class="button float_right" onclick="javascript: if(parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display == \'block\'){ parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'none\'; this.innerHTML=\''.$lang->my_spoiler_show.'\'; } else { parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'block\'; this.innerHTML=\''.$lang->my_spoiler_hide.'\'; }">'.$lang->my_spoiler_show.'</span>'.$lang->my_spoiler_spoil.':</div>
		<div class="spoiler_contenido" style="display: none;">$1</div>
	</div>',$message);

	// Creamos el MyCode complejo con comillas: [spoiler="título aquí"]contenido aquí[/spoiler]

	while(preg_match('#\[spoiler="(.*?)"\](.*?)\[\/spoiler\]#si',$message))
	$message = preg_replace('#\[spoiler="(.*?)"\](.*?)\[\/spoiler\]#si','<div class="spoiler">
		<div class="spoiler_cabecera"><span class="button float_right" onclick="javascript: if(parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display == \'block\'){ parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'none\'; this.innerHTML=\''.$lang->my_spoiler_show.'\'; } else { parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'block\'; this.innerHTML=\''.$lang->my_spoiler_hide.'\'; }">'.$lang->my_spoiler_show.'</span>$1:</div>
		<div class="spoiler_contenido" style="display: none;">$2</div>
	</div>',$message);

	// Creamos el MyCode complejo sin comillas: [spoiler=título aquí]contenido aquí[/spoiler]

	while(preg_match('#\[spoiler=(.*?)\](.*?)\[\/spoiler\]#si',$message))
	$message = preg_replace('#\[spoiler=(.*?)\](.*?)\[\/spoiler\]#si','<div class="spoiler">
		<div class="spoiler_cabecera"><span class="button float_right" onclick="javascript: if(parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display == \'block\'){ parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'none\'; this.innerHTML=\''.$lang->my_spoiler_show.'\'; } else { parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'block\'; this.innerHTML=\''.$lang->my_spoiler_hide.'\'; }">'.$lang->my_spoiler_show.'</span>$1:</div>
		<div class="spoiler_contenido" style="display: none;">$2</div>
	</div>',$message);
	
	return $message;
}

?>