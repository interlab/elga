<?php

function template_home()
{
    global $context, $scripturl, $user_info;

    echo '
    <style>
    </style>';
    
    echo '
    <h1>ElkArte Gallery</h1>
    <a href="', $scripturl, '?action=gallery;sa=add_file">Add new file</a>';

    $albums = $context['elga_albums'];

    // http://www.artlebedev.ru/tools/technogrette/html/thumbnails-center/
    
    echo '<div class="thumbnails">';
    $def_icon = 'http://simaru.tk/themes/MostlyBlue/images/_blue/logo_elk.png';
    foreach ($albums as $row) {
        echo '
        <ins class="thumbnail">
            <div class="r">
                <a href="', $scripturl, '?action=gallery;sa=album;id=', $row['id'], '">
                    <img src="', $row['icon'], '" alt="icon" height="64px" width="64px" />
                </a>
                <h4><a href="', $scripturl, '?action=gallery;sa=album;id=', $row['id'], '">' . $row['name'] . '</a></h4>
                ', $row['description'];

            if ($user_info['is_admin']) {
                echo '
                <p><a href="', $scripturl, '?action=gallery;sa=edit_album;id=', $row['id'], '" class="elga_edit">
                <i class="fa fa-edit fa-lg"></i> [Edit Album]</a></p>';
            }

        echo '
            </div>
        </ins>';
    }
    echo '</div>';
    
}

function template_add_file()
{
    global $context, $scripturl, $txt;
    
    echo '
    <h2 class="category_header">', $context['page_title'], '</h2>

    <form form action="', $scripturl, '?action=gallery;sa=', $context['elga_sa'], '" method="post" accept-charset="UTF-8"
        name="new_file" id="new_file" enctype="multipart/form-data">';
    
	if (!empty($context['errors']))
		echo '
				<div class="errorbox">Исправьте ошибки: <ul><li>', implode('</li><li>', $context['errors']), '</li></ul></div>';
    
    echo '
<div class="content">
    <dl class="settings">

        <dt>
            <label for="album">Album</label>
        </dt>
        <dd>

            <select name="album" id="album" tabindex="', $context['tabindex']++, '">
            <option value="0"></option>';

        foreach ($context['elga_albums'] as $row) {
            $selected = $context['elga_album'] == $row['id'];
            echo '
            <option value="', $row['id'], '"', ($selected ? ' selected="selected"' : ''), '>
                ', $row['name'], '
            </option>';
        }
        echo '
        </select>&nbsp;&nbsp';

    echo '
        </dd>
    
        <dt>
            <label for="title">Title</label>
        </dt>
        <dd>
            <input type="text" name="title" id="title" value="', !empty($context['elga_title']) ? $context['elga_title'] : '', '" tabindex="', $context['tabindex']++, '">
        </dd>
        <dt>
            <label for="descr">Ваше сообщение</label>
        </dt>
        <dd>
            <textarea id="descr" name="descr" cols="50" rows="10" tabindex="', $context['tabindex']++, '">', !empty($context['elga_descr']) ? $context['elga_descr'] : '', '</textarea>
        </dd>
        <dt>
            <label>Добавить файл</label>
        </dt>
        <dd>
            <input type="file" name="image" size="80" tabindex="', $context['tabindex']++, '" accept="image/*" />
        </dd>';
        
	if ($context['require_verification'])
	{
		template_verification_controls($context['visual_verification_id'], '
					<dt>
							' . $txt['verification'] . ':
					</dt>
					<dd>
							', '
					</dd>');
	}
        
    echo '
    </dl>
	<hr>
    <div class="submitbutton">
    <input type="submit" value="', $txt['sendtopic_send'], '" name="send" tabindex="', $context['tabindex']++, '" class="button_submit" />
    <input type="hidden" name="sa" value="', $context['elga_sa'], '">';
    if (isset($context['elga_file'])) {
        echo '
    <input type="hidden" name="id" value="', $context['elga_file']['id'], '" />';
    }
    echo '
    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />';
    if ($context['elga_sa'] === 'add_file') {
        echo '
    <input type="hidden" name="', $context['add_file_token_var'], '" value="', $context['add_file_token'], '" />';
    } elseif ($context['elga_sa'] === 'edit_file') {
        echo '
    <input type="hidden" name="', $context['edit_file_token_var'], '" value="', $context['edit_file_token'], '" />';
    }
    echo '
    </div>
</div>
    
    </form>';
}

function template_add_album()
{
    global $context, $scripturl, $txt;

    echo '
    <h2 class="category_header">', $context['page_title'], '</h2>

    <form form action="', $scripturl, '?action=gallery;sa=', $context['elga_sa'], '" method="post" accept-charset="UTF-8"
        name="new_file" id="new_file" enctype="multipart/form-data">';

	if (!empty($context['errors']))
		echo '
    <div class="errorbox">Исправьте ошибки: <ul><li>', implode('</li><li>', $context['errors']), '</li></ul></div>';

    echo '
<div class="content">
    <dl class="settings">

        <dt>
            <label for="album">Расположение</label>
        </dt>
        <dd>
            <select name="location" id="location" tabindex="', $context['tabindex']++, '">
            <option value="0">before</option>
            <option value="1">after</option>
            </select>

            <select name="album" id="album" tabindex="', $context['tabindex']++, '">
            <option value="0"></option>';

        foreach ($context['elga_albums'] as $row) {
            $selected = $context['elga_id'] == $row['id'];
            echo '
            <option value="', $row['id'], '"', ($selected ? ' selected="selected"' : ''), '>
                ', $row['name'], '
            </option>';
        }
        echo '
        </select>&nbsp;&nbsp';

    echo '
        </dd>

        <dt>
            <label for="title">Title</label>
        </dt>
        <dd>
            <input type="text" name="title" id="title" value="', !empty($context['elga_title']) ? $context['elga_title'] : '', '" tabindex="', $context['tabindex']++, '">
        </dd>
        <dt>
            <label for="descr">Ваше сообщение</label>
        </dt>
        <dd>
            <textarea id="descr" name="descr" cols="50" rows="10" tabindex="', $context['tabindex']++, '">', !empty($context['elga_descr']) ? $context['elga_descr'] : '', '</textarea>
        </dd>
        <dt>
            <label>Добавить файл</label>
        </dt>
        <dd>
            <input type="file" name="icon" size="80" tabindex="', $context['tabindex']++, '" accept="image/*" />
        </dd>';

	if ($context['require_verification'])
	{
		template_verification_controls($context['visual_verification_id'], '
					<dt>
							' . $txt['verification'] . ':
					</dt>
					<dd>
							', '
					</dd>');
	}

    echo '
    </dl>
	<hr>
    <div class="submitbutton">
    <input type="submit" value="', $txt['sendtopic_send'], '" name="send" tabindex="', $context['tabindex']++, '" class="button_submit" />
    <input type="hidden" name="sa" value="', $context['elga_sa'], '">';
    echo '
    <input type="hidden" name="id" value="', $context['elga_id'], '" />';
    echo '
    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />';
    if ($context['elga_sa'] === 'add_album') {
        echo '
    <input type="hidden" name="', $context['add_album_token_var'], '" value="', $context['add_album_token'], '" />';
    } else {
        echo '
    <input type="hidden" name="', $context['edit_album_token_var'], '" value="', $context['edit_album_token'], '" />';
    }
    echo '
    </div>
</div>

    </form>';
}

function template_album()
{
    global $context, $scripturl, $txt, $boardurl;
    
    echo '
    <h1>', $context['elga_album']['name'], '</h1>
    <a href="', $scripturl, '?action=gallery;sa=add_file;album=', $context['elga_album']['id'], '">Add new file</a>';

    if (empty($context['elga_files'])) {
        echo '<h1>В этом альбоме нет загруженных файлов.</h1>';

        return;
    }

    // echo '<h3>Page ', $context['page_info']['current_page'], '</h3>';

	// Show the page index... "Pages: [1]".
	template_pagesection('normal_buttons', 'right');
    
    echo '<div class="thumbnails">';
    foreach ($context['elga_files'] as $row) {
        echo '
        <ins class="thumbnail">
            <div class="r">
                <a href="', $row['icon'], '" class="fancybox" rel="group">
                    <img src="', $row['thumb'], '" alt="..." height="100px" width="100px" class="fancybox" />
                </a>
                <h4><a href="', $scripturl, '?action=gallery;sa=file;id=', $row['id'], '">' . $row['title'] . '</a></h4>
                Author: ', $row['member_name'], '
            </div>
        </ins>';
    }
    echo '
    </div>';

    if ($context['elga_is_next_start']) {
        echo '
    <div class="elga_scroll">
        <a href="', $scripturl, '?action=gallery;sa=album;type=js;id=', $context['elga_album']['id'], ';start=', $context['elga_next_start'],
            '" class="jscroll-next">next page</a>
    </div>';
    }
}

function template_album_js()
{
    global $context, $scripturl, $txt, $boardurl, $modSettings;

    // @ob_end_clean();
    // if (!empty($modSettings['enableCompressedOutput']))
        // ob_start('ob_gzhandler');
    // else
        // ob_start();
 
    // if (!$context['elga']['is_next_start'])
        // die('');

    // echo '<h3>Page ', $context['page_info']['current_page'], '</h3>';

    echo '<br><div class="thumbnails">';
    foreach ($context['elga_files'] as $row) {
        echo '
        <ins class="thumbnail">
            <div class="r">
                <a href="', $row['icon'], '" class="fancybox" rel="group">
                    <img src="', $row['thumb'], '" alt="..." height="100px" width="100px" class="fancybox" />
                </a>
                <h4><a href="', $scripturl, '?action=gallery;sa=file;id=', $row['id'], '">' . $row['title'] . '</a></h4>
                Author: ', $row['member_name'], '
            </div>
        </ins>';
    }
    echo '
    </div>';

    if (!$context['elga_is_next_start']) {
        // die(''); // end
    }
    else {
        echo '
    <a href="'. $scripturl. '?action=gallery;sa=album;type=js;id='. $context['elga_album']['id']. ';start='. $context['elga_next_start'].
        '" class="jscroll-next">next page</a>';
        // die('');
    }
}

function template_file()
{
    global $context, $scripturl, $txt, $boardurl;

    $row = $context['elga_file'];

    if ($context['elga_is_author']) {
        echo '
    <a href="', $scripturl, '?action=gallery;sa=edit_file;id=', $row['id'], '">Edit</a> | 
    <a href="', $scripturl, '?action=gallery;sa=remove_file;id=', $row['id'], ';', $context['session_var'], '=', $context['session_id'],
        '" onclick="return confirm(\'Remove this file?\');">Remove</a>';
    }

    echo '
    <div class="thumbnails">
        <ins class="thumbnail">
            <div class="r">
    <a href="', $row['icon'], '" class="fancybox" rel="group">
        <img src="', $row['icon'], '" alt="..." style="max-height:500px; max-width: 500px;" class="fancybox" />
    </a>
            </div>
        </ins>
    </div>';

    echo '
    Имя файла: <a href="', $scripturl, '?action=gallery;sa=file;id=', $row['id'], '">' . $row['orig_name'] . '</a><br>
    Title: ', $row['title'], '<br>
    Description: ', $row['description'], '<br>
    Author: <a href="', $scripturl, '?action=profile;u=', $row['id_member'], '">', $row['member_name'], '</a><br>';
}
