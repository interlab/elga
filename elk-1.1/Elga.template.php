<?php

function elga_show_buttons()
{
    global $context, $scripturl, $user_info, $txt;

    $left_links = [
        [
            'enable' => allowedTo('elga_create_files'),
            'name' => $txt['elga_create_file'],
            'url' => $scripturl . '?action=gallery;sa=add_file',
        ],
        [
            'enable' => allowedTo('elga_create_albums'),
            'name' => $txt['elga_create_album'],
            'url' => $scripturl . '?action=gallery;sa=add_album',
        ],
        [
            'enable' => allowedTo('elga_edit_albums'),
            'name' => $txt['elga_managealbums'],
            'url' => $scripturl . '?action=gallery;sa=managealbums',
        ],
    ];

    $right_links = [
        [
            'enable' => true,
            'name' => 'Все файлы',
            'url' => $scripturl . '?action=gallery;sa=browse',
        ],
        [
            'enable' => !$user_info['is_guest'],
            'name' => 'Мои файлы',
            'url' => $scripturl . '?action=gallery;sa=browse;user=' . $user_info['id'],
        ],
    ];


    echo '
    <div class="elga-buttons">';

    // left
    echo '
    <ul class="">';

    foreach ($left_links as $link) {
        if ($link['enable']) {
            echo '
        <li class="listlevel1">
            <a href="', $link['url'], '" class="linklevel1">', $link['name'], '</a>
        </li>';
        }
    }

    echo '
    </ul>';

    // right
    echo '
    <ul class="floatright">';

    foreach ($right_links as $link) {
        if ($link['enable']) {
            echo '
        <li class="listlevel1">
            <a href="', $link['url'], '" class="linklevel1">', $link['name'], '</a>
        </li>';
        }
    }

    echo '
    </ul>';

    echo '
    </div>
    <div class="clear"></div>';
}

function elga_show_buttons_albums($id)
{
    global $context, $user_info, $scripturl, $txt;

    $left_links = [
        [ 'url' => $scripturl . '?action=gallery;sa=add_file;album=' . $id, 'enable' => allowedTo('elga_create_files'), 'name' => $txt['elga_create_file'], ],
        [ 'url' => $scripturl . '?action=gallery;sa=edit_album;id=' . $id, 'enable' => allowedTo('elga_edit_albums'), 'name' => $txt['elga_edit_album'], ],
        [ 'url' => $scripturl . '?action=gallery;sa=add_album;album=' . $id, 'enable' => allowedTo('elga_create_albums'), 'name' => $txt['elga_create_album'], ],
    ];

    $right_links = [
        [ 'url' => $scripturl . '?action=gallery;sa=album;id=' . $id . ';user=' . $user_info['id'], 'enable' => !$user_info['is_guest'], 'name' => 'Мои файлы', ],
    ];

    echo '
    <div class="elga-buttons">';

    // left
    echo '
    <ul class="">';

    foreach ($left_links as $link) {
        if ($link['enable']) {
            echo '
        <li class="listlevel1">
            <a href="', $link['url'], '" class="linklevel1">', $link['name'], '</a>
        </li>';
        }
    }

    echo '
    </ul>';

    // right
    echo '
    <ul class="floatright">';

    foreach ($right_links as $link) {
        if ($link['enable']) {
            echo '
        <li class="listlevel1">
            <a href="', $link['url'], '" class="linklevel1">', $link['name'], '</a>
        </li>';
        }
    }

    echo '
    </ul>';

    echo '
    </div>
    <div class="clear"></div>';
}

function elga_thumbs($files, $usealbum=false)
{
    global $scripturl, $txt;

    $r = '';
    foreach ($files as $row) {
        $r .= '
        <div class="elga-thumb-file">
            <p class="elga-fname"><a href="' . $scripturl . '?action=gallery;sa=file;id=' . $row['id'] . '">' . $row['title'] . '</a></p>
            <p><a href="' . $row['icon'] . '" rel="group" title="' . $row['title'] . '" data-fancybox="">
                <img src="' . $row['thumb-url'] . '" alt="thumb" height="100px" width="100px" />
            </a></p>
            ' . ($usealbum ? 
            '<p><strong>' . $txt['elga_album'] . '</strong> <a href="' . $scripturl . '?action=gallery;sa=album;id=' . $row['alb_id'] . '">' . $row['alb_name'] . '</a></p>' : '') . '
            <p><i class="fa fa-eye" aria-hidden="true"></i> ' . $row['views'] . '</p>
            <p><i class="fa fa-hdd-o" aria-hidden="true"></i> ' . $row['hsize'] . '</p>
            <p><i class="fa fa-user" aria-hidden="true"></i> <a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['member_name'] . '</a></p>
        </div>';
    }
    $r .= '';

    return $r;
}

function template_empty()
{
    
}

function template_search()
{
    global $txt, $context, $scripturl;

    echo '
    <style>
    div.elga-search-form {
        margin: 0 auto;
        margin-bottom: 20px;
        padding: 10px;
        display: table;
        /*
        display: block;
        width: 50%;
        */
        border: 1px solid blue;
        border-color: #ddd;

        border-radius: 4px;
        -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.05);
        box-shadow: 0 1px 1px rgba(0,0,0,.05);
    	border-color: #ddd;
	}
	/*
	div.elga-search-form form > div {
		display: table;
		margin: 0 auto;
	}
	*/
	</style>
	<div class="elga-search-form">
	<form action="', $scripturl, '?action=gallery;sa=search" id="elga-search-form" class="elga-search-form">
		<input type="hidden" name="action" value="gallery" />
		<input type="hidden" name="sa" value="search" />
		<label for="q">Поиск</label> <input name="q" id="q" value="" required maxlength="80" size="40" />
		&nbsp;<button name="save" type="submit" value="send"><i class="fa fa-search" aria-hidden="true"></i>  Отправить</button>
		<br><br>
		<div>
		<fieldset>
			<legend>Настройки</legend>
		<span>Альбом</span>
		<select name="albums" required>
			<option value="0">Выбрать альбом</option>';

		foreach ($context['elga_albums'] as $album) {
			echo '
			<option value="', $album['id'], '">', (str_repeat('-', $album['depth'])), ' ', $album['name'], '</option>';
		}

	echo '
		</select>
		<br><br>
		<span>Где искать</span>
		<select name="location" id="location">
			<option value="name">Название</option>
			<option value="descr">Описание</option>
		</select>
		</fieldset>
		</div>
	</form>
	</div>';
}

function elga_show_sort_fields($album_id=0)
{
    global $txt; //$context, $scripturl, $user_info;

    echo '
    <div class="elga-sort-fields">
    <form>
    <select>
    <option value="0">Сортировать по</option>
    <option value="views-desc">', $txt['elga_sviews'], ' &#9660;</option>
    <option value="views-asc">', $txt['elga_sviews'], ' &#9650;</option>
    <option value="time_added-desc">', $txt['elga_sdate'], ' &#9660;</option>
    <option value="time_added-asc">', $txt['elga_sdate'], ' &#9650;</option>
    <option value="title-desc">', $txt['elga_stitle'], ' &#9660;</option>
    <option value="title-asc">', $txt['elga_stitle'], ' &#9650;</option>
    </select>
    <input type="hidden" name="album_id" value="', $album_id, '" />
    </form>
    </div>';
}

function elga_show_select_cats()
{
    // global $context, $scripturl, $user_info;

    echo '
    <div class="elga-select-cats">
    <form>
    <select>
    <option value="0">Выберите альбом для перехода</option>
    </select>
    </form>
    </div>';
}

function template_gallery_off()
{
    global $txt;

    echo $txt['elga_off'];
}

function template_managealbums()
{
    global $txt, $context, $scripturl;

    $ismove = !empty($context['elga_move_id']);
    $current = $ismove ? $context['elga_move_id'] : 0;

    echo '
    <div class="elga-buttons">
    <ul>
        <li class="listlevel1">
        <a href="'.$scripturl . '?action=gallery;sa=add_album"class="linklevel1">', $txt['elga_create_album'], '</a>
        </li>
    </ul>
    </div>
    <div class="clear"></div>
    <br>';

    if ( ! empty($context['elga_flashdata']) ) {
        echo $context['elga_flashdata'][2];
    }

    echo '
    <table class="elga-managealbum">
    <thead>
    <th colspan="', ($ismove ? '4' : '2'), '">Название</th>
    <th>Управление</th>
    </thead>
    <tbody>';
    foreach ($context['elga_albums'] as $album) {
        echo '
    <tr>';

        if ($ismove) {
            echo '
    <td>';

            if ($album['id'] != $context['elga_move_id']) {
                echo '
    <ul>
        <li class="listlevel1"><a href="', $scripturl, '?action=gallery;sa=managealbums;m=moveToPrevSiblingOf;id=', $album['id'], ';current=', $current, ';', $context['session_var'], '=', $context['session_id'], '" class="linklevel1"><i class="fa fa-long-arrow-right"></i></a></li>
        <li class="listlevel1"><a href="', $scripturl, '?action=gallery;sa=managealbums;m=moveToFirstChildOf;id=', $album['id'], ';current=', $current, ';', $context['session_var'], '=', $context['session_id'], '" class="linklevel1"><i class="fa fa-forward"></i></a></li>
    </ul>';
            }

        echo '
    </td>';
        }

        echo '
    <td style="height:35px; width: 35px;"><img src="', $album['icon'], '" height="34px" width="34px" /></td>
    <td><a href="', $album['url'], '">', $album['name'], '</a></td>';

        if ($ismove) {
            echo '
    <td>';

            if ($album['id'] != $context['elga_move_id']) {
                echo '
    <ul>
        <li class="listlevel1"><a href="', $scripturl, '?action=gallery;sa=managealbums;m=moveToLastChildOf;id=', $album['id'], ';current=', $current, ';', $context['session_var'], '=', $context['session_id'], '" class="linklevel1"><i class="fa fa-backward"></i></a></li>
        <li class="listlevel1"><a href="', $scripturl, '?action=gallery;sa=managealbums;m=moveToNextSiblingOf;id=', $album['id'], ';current=', $current, ';', $context['session_var'], '=', $context['session_id'], '" class="linklevel1"><i class="fa fa-long-arrow-left"></i></a></li>
    </ul>';
            }

        echo '
    </td>';
        }

        echo '
    <td class="elga-manage">
    <!--
    <ul>
        <li class="listlevel1"><a class="linklevel1"><i class="fa fa-arrows"></i> Перемещение</a></li>
        <li class="listlevel1"><a href="', $scripturl, '?action=gallery;sa=edit_album;id=', $album['id'], '" class="linklevel1"><i class="fa fa-pencil-square-o"></i> Редактировать</a></li>
    </ul>
    -->

    <a href="', $scripturl, '?action=gallery;sa=managealbums;m=move;id=', $album['id'], '"><i class="fa fa-arrows"></i>&nbsp;Перемещение</a>
    &nbsp;&nbsp;
    <a href="', $scripturl, '?action=gallery;sa=edit_album;id=', $album['id'], '"><i class="fa fa-pencil-square-o"></i>&nbsp;Редактировать</a>
    </td>
    </tr>';
    }
    echo '
    </tbody>
    </table>';
}

function elga_show_albums(array $albums = [])
{
    global $context, $scripturl, $user_info, $txt;

    if (empty($albums)) {
        return;
    } 

    echo '
    <h2 class="category_header elga-h2">', $txt['elga_albums'], '</h2>
    <div class="elga-thumbs-albums">';

    $nsubalbs = 0;
    foreach ($albums as $album) {
        if ( ! $nsubalbs ) {
            echo '
        <div class="elga-thumb-album">
            <div class="elga-r">
                <a href="', $scripturl, '?action=gallery;sa=album;id=', $album['id'], '">
                    <img src="', $album['icon'], '" alt="icon" height="64px" width="64px" />
                </a>
                <h4><a href="', $scripturl, '?action=gallery;sa=album;id=', $album['id'], '">' . $album['name'] . '</a></h4>
                <p class="elga-total">', $album['total'], ' файл(ов)</p>
                <p class="elga-album-descr">', Util::substr($album['description'], 0, 100), '</p>';

            if ($user_info['is_admin']) {
                echo '
            <p><a href="', $scripturl, '?action=gallery;sa=edit_album;id=', $album['id'], '" class="elga-edit">
            <i class="fa fa-edit fa-lg"></i> [', $txt['elga_edit_album'], ']</a></p>';
            }

            if ( $album['rightkey'] - $album['leftkey'] > 1) {
                $nsubalbs = ($album['rightkey'] - $album['leftkey'] - 1) / 2;
                echo '<hr>';
                continue;
            } else {
                echo '
            </div>
        </div>';
            }
        } else {
            echo '
        <h4 class="lefttext">
            <img height="20" width="20" src="', $album['icon'], '" alt="">
            <a href="', $scripturl, '?action=gallery;sa=album;id=', $album['id'], '">' . $album['name'] . '</a> (', $album['total'], ')
        </h4>';

            $nsubalbs -= 1;
            if ( ! $nsubalbs ) {
                echo '
                </div>
            </div>';
            }
        }
    }

    echo '
    </div>';
}

function template_home()
{
    global $context, $scripturl, $user_info, $txt;

    elga_show_buttons();

    elga_show_albums($context['elga_albums']);

    echo '
    <h2 class="category_header elga-h2">
    ', $txt['elga_last_files'], '
    <span class="nextlinks">Сортировка по: Дата | Название</span>
    </h2>';

    echo elga_thumbs($context['elga_last_files'], true);
}

function template_add_file()
{
    global $context, $scripturl, $txt;

    // loadTemplate('GenericHelpers');
    // loadTemplate('GenericControls');
    
    $context['elga-file-is-required'] = $context['elga_sa'] !== 'edit_file' ? 'required' : '';

    echo '
    <h2 class="category_header">', $context['page_title'], '</h2>

    <form action="', $scripturl, '?action=gallery;sa=', $context['elga_sa'], '" method="post" accept-charset="UTF-8"
        name="new_file" id="new_file" enctype="multipart/form-data">

    <div class="forumposts">
        <div class="editor_wrapper">

        <div class="errorbox"', empty($context['errors']) ? ' style="display: none;"' : '', '>Исправьте ошибки: <ul><li>', implode('</li><li>', $context['errors']), '</li></ul></div>';

    echo '
    <dl id="post_header">

        <dt>
            <label for="album">', $txt['elga-field-album'], '</label>
        </dt>
        <dd>
            <select name="album" id="album" tabindex="', $context['tabindex']++, '" required>
            <option></option>';

    foreach ($context['elga_albums'] as $row) {
        $selected = $context['elga_album'] == $row['id'];
        echo '
            <option value="', $row['id'], '"', ($selected ? ' selected="selected"' : ''), '>', (str_repeat('-', $row['depth'])), ' ', '
                ', $row['name'], '
            </option>';
    }

    echo '
        </select>&nbsp;&nbsp
        </dd>

        <dt>
            <label for="title">', $txt['elga-field-name'], '</label>
        </dt>
        <dd>
            <input type="text" name="title" id="title" size="80" value="', !empty($context['elga_title']) ? $context['elga_title'] : '', '" tabindex="', $context['tabindex']++, '" required>
        </dd>
    </dl>';

	echo '', template_control_richedit($context['post_box_name'], 'smileyBox_message', 'bbcBox_message');

    
    echo '
    <dl id="post_header">
        <dt class="elga-form-file-dt">
            <label>', $txt['elga-field-file'], '</label>
        </dt>
        <dd>
            <input type="file" name="image" tabindex="', $context['tabindex']++, '" accept="image/*"  ', $context['elga-file-is-required'], '>
        </dd>
    </dl>';

	// Show our submit buttons before any more options
	echo '
        <div id="post_confirm_buttons" class="submitbutton">
        ', template_control_richedit_buttons($context['post_box_name']), '
        </div>';

    if ($context['require_verification']) {
        echo '
    <dl class="settings">';
        template_verification_controls($context['visual_verification_id'], '
        <dt>
                ' . $txt['verification'] . ':
        </dt>
        <dd>
                ', '
        </dd>');
            echo '
    </dl>';
    }

    echo '
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
        </div><!-- end .forumposts -->
    </div><!-- end .editor_wrapper -->
    </form>';
}

function template_add_album()
{
    global $context, $scripturl, $txt;

    echo '
    <h2 class="category_header">', $context['page_title'], '</h2>

    <form action="', $context['elga_form_dest'], '" method="post" accept-charset="UTF-8"
        name="new_file" id="new_file" enctype="multipart/form-data">';

    if (!empty($context['errors'])) {
        echo '
    <div class="errorbox">', $txt['elga_fix_errors'], ' <ul><li>', implode('</li><li>', $context['errors']), '</li></ul></div>';
    }

    echo '
<div class="content">
    <dl class="settings">

        <dt>
            <label for="album">', $txt['elga_location'], '</label>
        </dt>
        <dd>
            <select name="location" id="location" tabindex="', $context['tabindex']++, '">';

    $v = $txt['elga_location_variants'];
    echo '
        <option value="not">', $v[0], '</option>
        <option value="moveToPrevSiblingOf">', $v[1], '</option>
        <option value="moveToNextSiblingOf">', $v[2], '</option>
        <option value="moveToLastChildOf"'.('add' === $context['elga_contr'] && $context['elga_id'] > 0 ? ' selected' : '').'>', $v[3], '</option>';

    echo '
            </select>

            <select name="album" id="album" tabindex="', $context['tabindex']++, '">
            <option value="0"></option>';

     foreach ($context['elga_albums2'] as $row) {
        if ('edit' === $context['elga_contr'] && $row['id'] == $context['elga_id']) {
            continue;
        }
        elseif ('add' === $context['elga_contr'] && $row['id'] == $context['elga_id']) {
            echo '
            <option value="', $row['id'], '" selected>', $row['name'], '</option>';
        }
        else {
            echo '
            <option value="', $row['id'], '">', $row['name'], '</option>';
        }
    }

    echo '
            </select>&nbsp;&nbsp
        </dd>

        <dt>
            <label for="title">', $txt['elga_album_name'], '</label>
        </dt>
        <dd>
            <input type="text" name="title" id="title" value="', !empty($context['elga_title']) ? $context['elga_title'] : '', '" tabindex="', $context['tabindex']++, '" required>
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
    <input type="hidden" name="sa" value="', $context['elga_sa'], '">
    <input type="hidden" name="id" value="', $context['elga_id'], '" />
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

    elga_show_albums($context['elga_album']['descendants']);

    echo '
    <h2 class="category_header elga-h2">
    ', $txt['elga_last_files'], '
    </h2>';

    elga_show_buttons_albums($context['elga_album']['id']);

    echo '
    <div class="elga-second-row">';
    // Show the page index... "Pages: [1]".
    template_pagesection('normal_buttons', 'right');
    echo elga_show_sort_fields($context['elga_album']['id']);
    echo elga_show_select_cats();
    echo '
    </div>';

    if (empty($context['elga_files'])) {
        echo '
    <h1>', $txt['elga_album_empty'], '</h1>';

        return;
    }

    echo '
    <div class="elga-thumbs">
    ', elga_thumbs($context['elga_files'], false), '
    </div>';

    if ($context['elga_is_next_start']) {
        echo '
    <div class="elga-scroll">
        <a href="', $scripturl, '?action=gallery;sa=album;type=js;id=',
        $context['elga_album']['id'], ';start=', $context['elga_next_start'],
        (empty($context['elga_sort']) ? '' : ';sort=' . $context['elga_sort']),
        ($context['elga_req_user'] ? ';user=' . $context['elga_req_user'] : ''),
            '" class="jscroll-next">next page</a>
    </div>';
    }
}

function template_album_js()
{
    global $context, $scripturl, $txt, $boardurl, $modSettings;

    $usealbum = false;
    // @ob_end_clean();
    // if (!empty($modSettings['enableCompressedOutput']))
        // ob_start('ob_gzhandler');
    // else
        // ob_start();
 
    // if (!$context['elga']['is_next_start'])
        // die('');

    echo '', elga_thumbs($context['elga_files'], $usealbum), '';

    if (!$context['elga_is_next_start']) {
        // die(''); // end
    }
    else {
        echo '
    <a href="', $scripturl, '?action=gallery;sa=album;type=js;id=',
    $context['elga_album']['id'], ';start=', $context['elga_next_start'],
    (empty($context['elga_sort']) ? '' : ';sort=' . $context['elga_sort']),
    ($context['elga_req_user'] ? ';user=' . $context['elga_req_user'] : ''),
        '" class="jscroll-next">next page</a>';
        // die('');
    }
}

function template_browse()
{
    global $context, $scripturl, $txt, $boardurl;

    elga_show_buttons();

    echo '
    <h2 class="category_header elga-h2">
        Browse files
    </h2>';

    // echo elga_show_sort_fields();
    // echo elga_show_select_cats();

    if (empty($context['elga_files'])) {
        echo '
    <h1>Файлы не найдены</h1>';

        return;
    }

    echo '
    <div class="elga-header-box">
        <div class="elga-page-box">';

    template_pagesection('normal_buttons', 'right');

    echo '
        </div>
        <div class="elga-sort-box">',
    elga_show_sort_fields(), 
    elga_show_select_cats(), '
        </div>
    </div>';

    echo '
    <div class="elga-thumbs">
    ', elga_thumbs($context['elga_files'], true), '
    </div>';

    if ($context['elga_is_next_start']) {
        echo '
    <div class="elga-scroll">
        <a href="', $context['elga_url_js'], ';start=', $context['elga_next_start'], '" class="jscroll-next">next page</a>
    </div>';
    }
}

function template_browse_js()
{
    global $context, $scripturl, $txt, $boardurl, $modSettings;

    $usealbum = true;

    echo '', elga_thumbs($context['elga_files'], $usealbum), '';

    if (!$context['elga_is_next_start']) {
        // die(''); // end
    }
    else {
        echo '
    <a href="', $context['elga_url_js'], ';start=', $context['elga_next_start'], '" class="jscroll-next">next page</a>';
        // die('');
    }
}

function template_file()
{
    global $context, $scripturl, $txt, $boardurl, $modSettings;

    $row = $context['elga_file'];

    echo '
  <script>
  $(function() {
    $( "#elga-tabs-file" ).tabs();
  });
  </script>
    ';

    if ($context['elga_is_author']) {
        echo '
    <ul>
    <li class="listlevel1">
        <a href="', $scripturl, '?action=gallery;sa=edit_file;id=', $row['id'], '" class="linklevel1">', $txt['elga_edit'], '</a>
    </li>
    <li class="listlevel1">
    <a href="', $scripturl, '?action=gallery;sa=remove_file;id=', $row['id'], ';', $context['session_var'], '=', $context['session_id'],
        '" onclick="return confirm(\'Remove this file?\');" class="linklevel1">', $txt['elga_remove'], '</a>
    </li>
    </ul>
    <div class="clear"></div>';
    }

    echo '
    <div class="elga-photo-container">
        <div class="elga-arrow' . ($row['prev_id'] ? '' : ' elga-disabled') . '">' . ($row['prev_id'] ? '<a href="' . $scripturl . '?action=gallery;sa=file;id=' . $row['prev_id']. '">' : '') . '&#8592; Пред.</a></div>
        <div class="elga-display">
    <a href="', $row['icon'], '" data-fancybox="" rel="group">
        <img src="', $row['preview-url'], '" alt="image" style="max-height:500px; max-width: 500px;" />
    </a>
        </div>
        <div class="elga-arrow' . ($row['next_id'] ? '' : ' elga-disabled') . '">' . ($row['next_id'] ? '<a href="' . $scripturl . '?action=gallery;sa=file;id=' . $row['next_id']. '">' : '') . 'След. &#8594;</a></div>
    </div>';

    // tabs
    echo '
<div id="elga-tabs-file">
    <ul>
        <li><a href="#elga-tabs-descr">Описание</a></li>
        <li><a href="#elga-tabs-exif">EXIF</a></li>
    </ul>
    <div id="elga-tabs-descr">


    <div class="elga-file-descr">
    <dl>
        <dt>', $txt['elga_fname'], '</dt>
        <dd>' . $row['orig_name'] . '&nbsp;&nbsp;<a href="', $row['img-url'], '">[Открыть]</a>
        &nbsp;&nbsp;<a href="', $row['img-download-url'], '">[Скачать]</a></dd>

        <dt>', $txt['elga_size'], '</dt>
        <dd>', $row['hsize'], '</dd>

        <dt>', $txt['elga_views'], '</dt>
        <dd>', $row['views'],'</dd>

        <dt>', $txt['elga_author'], '</dt>
        <dd><a href="', $scripturl, '?action=profile;u=', $row['id_member'], '">', $row['member_name'], '</a></dd>

        <dt>', $txt['elga_date'], '</dt>
        <dd>', standardTime($row['time_added']), '</dd>

        <dt>', $txt['elga_bbcode_link'], '</dt>
        <dd>
        &nbsp;<input id="elga-bbcode-copy" value="', $row['img-bbc'], '" size="60" readonly>
        &nbsp;<button id="elga-copy-btn">Скопировать</button>
        &nbsp;<span id="elga-copy-answer"></span>
        </dd>

        <dt>', $txt['elga_ftitle'], '</dt>
        <dd>', $row['title'], '</dd>

        <dt>', $txt['elga_descr'], '</dt>
        <dd>', $row['description'], '</dd>
    </dl>
    </div>


    </div>
    <div id="elga-tabs-exif">';

	/*
	$exif =& $row['exif'];
	// dump($exif);

	if (is_array($exif) || $exif instanceof Traversable) {
		if (empty($exif)) {
			echo 'Нет данных Exif.';
		} else {
			// dump($exif);
			foreach ($exif as $k => $v) {
				echo $k, ': ', $v, '<br>';
			}
		}
	}
	*/

	/*
	$exif =& $row['exif'];
	// dump($exif);

	if (empty($exif)) {
		echo 'Нет данных Exif.';
	} else {
		// dump($exif);
		foreach ($exif as $k => $v) {
			if (is_array($v)) { $v = 'Array'; }
			echo $k, ': ', $v, '<br>';
		}
	}
	*/

	echo '
	</div>
</div>';

    echo elga_show_select_cats();

    //  Disqus comments block
    if (!empty($modSettings['elga_disquz_enable']) && !empty($modSettings['elga_disquz_embed'])) {
    echo '
<div id="disqus_thread"></div>
<script>
    /**
     *  RECOMMENDED CONFIGURATION VARIABLES: EDIT AND UNCOMMENT THE SECTION BELOW TO INSERT DYNAMIC VALUES FROM YOUR PLATFORM OR CMS.
     *  LEARN WHY DEFINING THESE VARIABLES IS IMPORTANT: https://disqus.com/admin/universalcode/#configuration-variables
     */

    var disqus_config = function () {
        this.page.url = "', $scripturl, '?action=gallery;sa=file;id=', $row['id'], '"; // Replace PAGE_URL with your page\'s canonical URL variable
        this.page.identifier = ', $row['id'], '; // Replace PAGE_IDENTIFIER with your page\'s unique identifier variable
    };

    (function() {  // DON\'T EDIT BELOW THIS LINE
        var d = document, s = d.createElement(\'script\');

        s.src = \'//', $modSettings['elga_disquz_embed'], '.disqus.com/embed.js\';

        s.setAttribute(\'data-timestamp\', +new Date());
        (d.head || d.body).appendChild(s);
    })();
</script>
<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript" rel="nofollow">comments powered by Disqus.</a></noscript>';
    }
}
