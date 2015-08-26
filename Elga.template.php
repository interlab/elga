<?php

function template_home()
{
    global $context, $scripturl;

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
                text for text ...
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
    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
    <input type="hidden" name="', $context['add_file_token_var'], '" value="', $context['add_file_token'], '" />
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

    echo '<div class="thumbnails">';
    foreach ($context['elga_files'] as $row) {
        echo '
        <ins class="thumbnail">
            <div class="r">
                <a href="', $row['icon'], '" class="fancybox" rel="group">
                    <img src="', $row['icon'], '" alt="..." height="100px" width="100px" class="fancybox" />
                </a>
                <h4><a href="', $scripturl, '?action=gallery;sa=file;id=', $row['id'], '">' . $row['title'] . '</a></h4>
                Author: ', $row['member_name'], '
            </div>
        </ins>';
    }
    echo '</div>';
}

function template_file()
{
    global $context, $scripturl, $txt, $boardurl;

    $row = $context['elga_file'];

    echo '
    <a href="', $scripturl, '?action=gallery;sa=edit_file;id=', $row['id'], '">Edit</a>';
    
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
