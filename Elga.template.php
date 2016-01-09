<?php

function elga_html_buttons()
{
    global $context, $scripturl, $user_info;

    $links = [];
    if (allowedTo('elga_create_files')) {
        $links[] = [$scripturl . '?action=gallery;sa=add_file', 'Add new file'];
    }
    if (allowedTo('elga_create_albums')) {
        $links[] = [$scripturl . '?action=gallery;sa=add_album', 'Add new album'];
    }

    if (!empty($links)) {
        echo '
    <div class="elga-buttons">
    <ul>';

    foreach ($links as $link) {
        echo '
        <li class="listlevel1"><a href="', $link[0], '"  class="linklevel1">', $link[1], '</a></li>';
    }

    echo '
    </ul>
    </div>
    <div class="clear"></div>';
    }
}

function template_empty()
{
    
}

function elga_show_select_cats()
{
     global $context, $scripturl, $user_info;
     
     echo '
     <div class="elga-select-cats">
     <form>
     <select>
        <option value="0" data-elgaseltxt="Выберите альбом для перехода">Выберите альбом для перехода</option>
     </select>
     </form>
     </div>';
}

function template_home()
{
    global $context, $scripturl, $user_info;

    // http://www.artlebedev.ru/tools/technogrette/html/thumbnails-center/
    // echo '
    // <style>
    // </style>';

    elga_html_buttons();

    echo '
    <div class="elga_thumbnails">';

    foreach ($context['elga_albums'] as $album) {
        echo '
        <ins class="elga_thumbnail">
            <div class="elga_r">
                <a href="', $scripturl, '?action=gallery;sa=album;id=', $album['id'], '">
                    <img src="', $album['icon'], '" alt="icon" height="64px" width="64px" />
                </a>
                <h4><a href="', $scripturl, '?action=gallery;sa=album;id=', $album['id'], '">' . $album['name'] . '</a></h4>
                <p class="elga-total">', $album['total'], ' файл(ов)</p>
                <p class="elga_album_descr">', Util::substr($album['description'], 0, 100), '</p>';

            if ($user_info['is_admin']) {
                echo '
                <p><a href="', $scripturl, '?action=gallery;sa=edit_album;id=', $album['id'], '" class="elga_edit">
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

    <form action="', $scripturl, '?action=gallery;sa=', $context['elga_sa'], '" method="post" accept-charset="UTF-8"
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

    <form action="', $scripturl, '?action=gallery;sa=', $context['elga_sa'], '" method="post" accept-charset="UTF-8"
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
    <div class="elga-buttons">
    <ul>
        <li class="listlevel1">
    <a href="', $scripturl, '?action=gallery;sa=add_file;album=', $context['elga_album']['id'], '" class="linklevel1">Add new file</a>
        </li>
    </ul>
    </div>';

    echo elga_show_select_cats();

    if (empty($context['elga_files'])) {
        echo '<h1>В этом альбоме нет загруженных файлов.</h1>';

        return;
    }

    // Show the page index... "Pages: [1]".
    template_pagesection('normal_buttons', 'right');

    echo '<div class="elga_thumbnails">';
    foreach ($context['elga_files'] as $row) {
        echo '
        <ins class="elga_thumbnail elga_item">
            <div class="elga_r">
                <a href="', $row['icon'], '" class="fancybox" rel="group">
                    <img src="', $row['thumb'], '" alt="..." height="100px" width="100px" class="fancybox" />
                </a>
                <h4><a href="', $scripturl, '?action=gallery;sa=file;id=', $row['id'], '">' . $row['title'] . '</a></h4>
                Size: ', (round($row['fsize'] / 1024, 2) . ' ' . $txt['kilobyte']), '<br>
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

    //echo '<div class="elga_thumbnails">';
    foreach ($context['elga_files'] as $row) {
        echo '
        <ins class="elga_thumbnail elga_item">
            <div class="elga_r">
                <a href="', $row['icon'], '" class="fancybox" rel="group">
                    <img src="', $row['thumb'], '" alt="..." height="100px" width="100px" class="fancybox" />
                </a>
                <h4><a href="', $scripturl, '?action=gallery;sa=file;id=', $row['id'], '">' . $row['title'] . '</a></h4>
                Size: ', (round($row['fsize'] / 1024, 2) . ' ' . $txt['kilobyte']), '<br>
                Author: ', $row['member_name'], '
            </div>
        </ins>';
    }
    //echo '</div>';

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
    <ul>
    <li class="listlevel1">
        <a href="', $scripturl, '?action=gallery;sa=edit_file;id=', $row['id'], '" class="linklevel1">Edit</a>
    </li>
    <li class="listlevel1">
    <a href="', $scripturl, '?action=gallery;sa=remove_file;id=', $row['id'], ';', $context['session_var'], '=', $context['session_id'],
        '" onclick="return confirm(\'Remove this file?\');" class="linklevel1">Remove</a>
    </li>
    </ul>
    <div class="clear"></div>';
    }

    echo '
    <div class="elga-photo-container">
        <div class="elga-arrow' . ($row['prev_id'] ? '' : ' elga-disabled') . '">' . ($row['prev_id'] ? '<a href="' . $scripturl . '?action=gallery;sa=file;id=' . $row['prev_id']. '">' : '') . '&#8592; Пред.</a></div>
        <div class="elga_display">
    <a href="', $row['icon'], '" class="fancybox" rel="group">
        <img src="', $row['icon'], '" alt="..." style="max-height:500px; max-width: 500px;" class="fancybox" />
    </a>
        </div>
        <div class="elga-arrow' . ($row['next_id'] ? '' : ' elga-disabled') . '">' . ($row['next_id'] ? '<a href="' . $scripturl . '?action=gallery;sa=file;id=' . $row['next_id']. '">' : '') . 'След. &#8594;</a></div>
    </div>';

    echo '
    <div class="elga-file-descr">
    <strong>Имя файла:</strong> <a href="', $scripturl, '?action=gallery;sa=file;id=', $row['id'], '">' . $row['orig_name'] . '</a><br>
    <strong>Size:</strong> ', (round($row['fsize'] / 1024, 2) . ' ' . $txt['kilobyte']), '<br>
    <strong>Author:</strong> <a href="', $scripturl, '?action=profile;u=', $row['id_member'], '">', $row['member_name'], '</a><br>
    <strong>Дата загрузки:</strong> ', standardTime($row['time_added']), '<br>
    <strong>Title:</strong> ', $row['title'], '<br>
    <strong>Description:</strong> ', $row['description'], '<br>
    </div>';

    echo elga_show_select_cats();
}
