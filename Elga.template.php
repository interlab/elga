<?php

function elga_show_buttons()
{
    global $context, $scripturl, $user_info, $txt;

    $links = [];
    if (allowedTo('elga_create_files')) {
        $links[] = [$scripturl . '?action=gallery;sa=add_file', $txt['elga_create_file']];
    }
    if (allowedTo('elga_create_albums')) {
        $links[] = [$scripturl . '?action=gallery;sa=add_album', $txt['elga_create_album']];
    }
    if (allowedTo('elga_edit_albums')) {
        $links[] = [$scripturl . '?action=gallery;sa=managealbums', $txt['elga_managealbums']];
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

function elga_thumbs($files, $usealbum=false)
{
    global $scripturl, $txt;

    $r = '
    <div class="elga-thumbs">';
    foreach ($files as $row) {
        $r .= '
        <div class="elga-thumb-file">
            <p class="elga-fname"><a href="' . $scripturl . '?action=gallery;sa=file;id=' . $row['id'] . '">' . $row['title'] . '</a></p>
            <p><a href="' . $row['icon'] . '" class="fancybox" rel="group" title="' . $row['title'] . '">
                <img src="' . $row['thumb-url'] . '" alt="..." height="100px" width="100px" class="fancybox" />
            </a></p>
            ' . ($usealbum ? 
            '<p><strong>' . $txt['elga_album'] . '</strong> <a href="' . $scripturl . '?action=gallery;sa=album;id=' . $row['alb_id'] . '">' . $row['alb_name'] . '</a></p>' : '') . '
            <p><i class="fa fa-eye" aria-hidden="true"></i> ' . $row['views'] . '</p>
            <p><i class="fa fa-hdd-o" aria-hidden="true"></i> ' . $row['hsize'] . '</p>
            <p><i class="fa fa-user" aria-hidden="true"></i> <a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['member_name'] . '</a></p>
        </div>';
    }
    $r .= '
    </div>';

    return $r;
}

function template_empty()
{
    
}

function elga_show_sort_fields($album_id)
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
            <i class="fa fa-edit fa-lg"></i> [', $txt['edit_album'], ']</a></p>';
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

    echo '
    <h2 class="category_header">', $context['page_title'], '</h2>

    <form action="', $scripturl, '?action=gallery;sa=', $context['elga_sa'], '" method="post" accept-charset="UTF-8"
        name="new_file" id="new_file" enctype="multipart/form-data">';

    if (!empty($context['errors'])) {
        echo '
                <div class="errorbox">Исправьте ошибки: <ul><li>', implode('</li><li>', $context['errors']), '</li></ul></div>';
    }

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
        </select>&nbsp;&nbsp

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

    foreach ($txt['elga_location_variants'] as $k => $v) {
        echo '
            <option value="', $k, '">', $v, '</option>';
    }

    echo '
            </select>

            <select name="album" id="album" tabindex="', $context['tabindex']++, '">
            <option value="0"></option>';

     foreach ($context['elga_albums'] as $row) {
        if ($row['id'] == $context['elga_id']) {
            continue;
        }
        echo '
            <option value="', $row['id'], '">', $row['name'], '</option>';
    }

    echo '
            </select>&nbsp;&nbsp
        </dd>

        <dt>
            <label for="title">', $txt['elga_album_name'], '</label>
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

    if (allowedTo('elga_create_files')) {
        echo '
    <div class="elga-buttons">
    <ul>
        <li class="listlevel1">
    <a href="', $scripturl, '?action=gallery;sa=add_file;album=', $context['elga_album']['id'], '" class="linklevel1">', $txt['elga_create_file'], '</a>
        </li>
    </ul>
    </div>';
    }

    echo elga_show_sort_fields($context['elga_album']['id']);
    echo elga_show_select_cats();

    if (empty($context['elga_files'])) {
        echo '<h1>', $txt['elga_album_empty'], '</h1>';

        return;
    }

    // Show the page index... "Pages: [1]".
    template_pagesection('normal_buttons', 'right');

    echo elga_thumbs($context['elga_files']);

    if ($context['elga_is_next_start']) {
        echo '
    <div class="elga-scroll">
        <a href="', $scripturl, '?action=gallery;sa=album;type=js;id=',
        $context['elga_album']['id'], ';start=', $context['elga_next_start'],
        (empty($context['elga_sort']) ? '' : ';sort=' . $context['elga_sort']),
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

    //echo '<div class="elga-thumbs">';
    foreach ($context['elga_files'] as $row) {
        echo '
        <div class="elga-thumb-file">
            <p class="elga-fname"><a href="' . $scripturl . '?action=gallery;sa=file;id=' . $row['id'] . '">' . $row['title'] . '</a></p>
            <p><a href="' . $row['icon'] . '" class="fancybox" rel="group" title="' . $row['title'] . '">
                <img src="' . $row['thumb-url'] . '" alt="..." height="100px" width="100px" class="fancybox" />
            </a></p>
            ' . ($usealbum ? 
            '<p><strong>' . $txt['elga_album'] . '</strong> <a href="' . $scripturl . '?action=gallery;sa=album;id=' . $row['alb_id'] . '">' . $row['alb_name'] . '</a></p>' : '') . '
            <p><i class="fa fa-eye" aria-hidden="true"></i> ' . $row['views'] . '</p>
            <p><i class="fa fa-hdd-o" aria-hidden="true"></i> ' . $row['hsize'] . '</p>
            <p><i class="fa fa-user" aria-hidden="true"></i> <a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['member_name'] . '</a></p>
        </div>';
    }
    //echo '</div>';

    if (!$context['elga_is_next_start']) {
        // die(''); // end
    }
    else {
        echo '
    <a href="', $scripturl, '?action=gallery;sa=album;type=js;id=',
    $context['elga_album']['id'], ';start=', $context['elga_next_start'],
    (empty($context['elga_sort']) ? '' : ';sort=' . $context['elga_sort']),
        '" class="jscroll-next">next page</a>';
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
    <a href="', $row['icon'], '" class="fancybox" rel="group">
        <img src="', $row['preview-url'], '" alt="..." style="max-height:500px; max-width: 500px;" class="fancybox" />
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
    <table>
    <tbody>
    <tr>
        <td>', $txt['elga_fname'], '</td><td>' . $row['orig_name'] . '&nbsp;&nbsp;<a href="', $row['img-url'], '">[Открыть]</a>
        &nbsp;&nbsp;<a href="', $row['img-download-url'], '">[Скачать]</a></td>
    </tr><tr>
        <td>', $txt['elga_size'], '</td><td>', $row['hsize'], '</td>
    </tr><tr>
        <td>', $txt['elga_views'], '</td><td>', $row['views'],'</td>
    </tr><tr>
        <td>', $txt['elga_author'], '</td>
        <td><a href="', $scripturl, '?action=profile;u=', $row['id_member'], '">', $row['member_name'], '</a></td>
    </tr><tr>
        <td>', $txt['elga_date'], '</td>
        <td>', standardTime($row['time_added']), '</td>
    </tr><tr>
        <td>', $txt['elga_bbcode_link'], '</td><td>
        &nbsp;<input id="elga-bbcode-copy" value="', $row['img-bbc'], '" size="60" readonly>
        &nbsp;<button id="elga-copy-btn">Скопировать</button>
        &nbsp;<span id="elga-copy-answer"></span></td>
    </tr><tr>
        <td>', $txt['elga_ftitle'], '</td><td>', $row['title'], '</td>
    </tr><tr>
        <td>', $txt['elga_descr'], '</td><td>', $row['description'], '</td>
    </tr>
    </tbody>
    </table>
    </div>


    </div>
    <div id="elga-tabs-exif">', $row['exif'], '</div>
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
