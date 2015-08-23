<?php

function template_oldhome()
{
    global $context;

    echo '
    <style>
    .elga_table {
        border: 1px solid black;
        width: 100%;
        border-collapse: collapse;
    }
    .elga_table td {
        border: 1px solid #C4C4C4;
    }
    
    div.elga_content {
        margin: 0 auto;
        width: 100%;
    }
    div.elga_cell {
        min-width: 25%;
        min-height: 10em;
        border: 1px solid #C4C4C4;
    }
    div.elga_cell a {
        font-weight: bold;
        font-size: 110%;
    }
    
    
.thumbnails
{
/* Компенсируем отступы между float-блоками, чтобы они занимали все доступное пространство */
margin: -3em 0 0 -2em;

/* Выравнивание по центру */
text-align: center;
}

.thumbnail
{
/* Убираем подчеркивание у элемента ins,
который был использован для совместимости со старыми версиями Internet Explorer */
text-decoration: none;

/* Следующее правило для Firefox 2 */
display: -moz-inline-box;

/* а это для остальных */
display: inline-block;

vertical-align: top;

/* Убираем выравнивание по центру */
text-align: left;

/* Отступы между блоками */
margin: 3em 0 0 2em;
}

.thumbnail .r
{
/* Если есть необходимость, то свойства padding, border, background и position со значением relative
лучше задавать у этого элемента -- это несколько расширит количество поддерживаемых версий браузеров */

/* Задаем минимальную ширину по тексту */
width: 14em;
min-height: 10em;

/* Минимальная ширина в пикселях будет автоматически рассчитываться по ширине картинки */
float: left;

border: 1px solid #C4C4C4;
}

.thumbnail .r a {
        font-weight: bold;
        font-size: 110%;
    }
    </style>';
    echo '<h1>Elga is simple Elkarte gallery</h1>';
    echo '<table class="elga_table">';
    
    $albums = $context['elga']['albums'];
    $i = 0;
    $column = 3;
    
    foreach ($albums as $row) {
        
        $i++;

        if ($i === 1) {
            echo '<tr>';
        }

        if ($i % ($column + 1) === 0) {
            echo '
            </tr>
            <tr><td>';
        }
        else {
            echo '<td>';
        }

        echo '', $row['name'], '</td>';

    }

    while($i % $column !== 0) {
        $i++;
        echo '<td></td>';
    }

    echo '</table>';
    
    $albums = $context['elga']['albums'];
    $i = 0;
    $column = 3;
    
    echo '<div class="elga_content">';
    foreach ($albums as $row) {
        echo '<div class="floatleft elga_cell"><a href="">', $row['name'], '</a></div>';
    }
    echo '</div>';
    
    echo '<br class="clear"><br class="clear"><br class="clear">';

    // http://www.artlebedev.ru/tools/technogrette/html/thumbnails-center/
    echo '<div class="thumbnails">';
    foreach ($albums as $row) {
        echo '
        <ins class="thumbnail">
            <div class="r">
                <a href="">', $row['name'], '</a>
            </div>
        </ins>';
    }
    echo '</div>';
    
    echo '
<div class="container">
  <div class="row">
    <div class="col-sm-4">
      Content of the left column.
    </div>
    <div class="col-sm-8">
      Content of the right column.
    </div>
  </div>
</div>';

    echo '
    <div class="container">

        ';
    $i = 0;
    $html = '';
    foreach ($albums as $row) {
        $i++;
        if ($i === 1) {
            $html .= '
        <div class="row">';
        }
        
        if ($i % 4 === 0) {
            $html .= '
        </div>
        <div class="row">';
        }
        
        $html .= '
        <div class="col-sm-4 elga_cell">
            <img src="http://simaru.tk/themes/MostlyBlue/images/_blue/logo_elk.png" alt="..." class="img-rounded">
            <h4><a href="">' . $row['name'] . '</a></h4>
            text for text
        </div>';
    }
    echo $html, '
        </div>
    </div>';
    
}

function template_home()
{
    global $context, $scripturl;

    echo '
    <style>
    </style>';
    
    echo '
    <h1>ElkArte Gallery</h1>
    <a href="', $scripturl, '?action=gallery;sa=add_file">Add new file</a>';

    $albums = $context['elga']['albums'];

    // http://www.artlebedev.ru/tools/technogrette/html/thumbnails-center/
    
    echo '<div class="thumbnails">';
    $def_icon = 'http://simaru.tk/themes/MostlyBlue/images/_blue/logo_elk.png';
    foreach ($albums as $row) {
        echo '
        <ins class="thumbnail">
            <div class="r">
                <img src="', $row['icon'] ? $row['icon'] : $def_icon, '" alt="..." />
                <h4><a href="">' . $row['name'] . '</a></h4>
                text for text ...
            </div>
        </ins>';
    }
    echo '</div>';
    
}

function template_ugli()
{
    global $context;

    echo '
    <style>
    .elga_table {
        border: 1px solid black;
        width: 100%;
        border-collapse: collapse;
    }
    .elga_table td {
        border: 1px solid #C4C4C4;
    }
    
    div.elga_content {
        margin: 0 auto;
        width: 100%;
    }
    div.elga_cell {
        min-width: 25%;
        min-height: 10em;
        border: 1px solid #C4C4C4;
    }
    div.elga_cell a {
        font-weight: bold;
        font-size: 110%;
    }
    
    
.thumbnails
{
    /* Компенсируем отступы между float-блоками, чтобы они занимали все доступное пространство */
    margin: -3em 0 0 -2em;

    /* Выравнивание по центру */
    text-align: center;
}

.thumbnail
{
    /* Убираем подчеркивание у элемента ins,
    который был использован для совместимости со старыми версиями Internet Explorer */
    text-decoration: none;

    /* Следующее правило для Firefox 2 */
    display: -moz-inline-box;

    /* а это для остальных */
    display: inline-block;

    vertical-align: top;

    /* Убираем выравнивание по центру */
    /*text-align: left;*/

    /* Отступы между блоками */
    margin: 3em 0 0 2em;
    
    padding: 4px;
    background-color: #FFF;
    border: 1px solid #DDD;
    border-radius: 4px;
}

.thumbnail .r
{
    /* Если есть необходимость, то свойства padding, border, background и position со значением relative
    лучше задавать у этого элемента -- это несколько расширит количество поддерживаемых версий браузеров */

    /* Задаем минимальную ширину по тексту */
    min-width: 14em;
    min-height: 10em;

    /* Минимальная ширина в пикселях будет автоматически рассчитываться по ширине картинки */
    /*float: left;*/
    display: inline-block;

    border: 1px solid #C4C4C4;
}

.thumbnail .r a {
        font-weight: bold;
        font-size: 110%;
    }
    </style>';
    
    echo '
    <style>
    .elga_cell {
        border: 1px solid #C4C4C4;
        border-radius: 8px;
    }
    </style>';
    
    echo '
    <h1>ElkArte Gallery</h1>';

    $albums = $context['elga']['albums'];

    // http://www.artlebedev.ru/tools/technogrette/html/thumbnails-center/
    
    echo '<div class="thumbnails">';
    $def_icon = 'http://simaru.tk/themes/MostlyBlue/images/_blue/logo_elk.png';
    foreach ($albums as $row) {
        echo '
        <ins class="thumbnail">
            <div class="r">
                <img src="', $row['icon'] ? $row['icon'] : $def_icon, '" alt="..." />
                <h4><a href="">' . $row['name'] . '</a></h4>
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
    <h2 class="category_header">New File</h2>

    <form form action="', $scripturl, '?action=gallery;sa=add_file" method="post" accept-charset="UTF-8"
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
            <option value=""></option>';

        $albums = $context['elga_albums'];
        foreach ($albums as $row) {
            echo '
            <option value="', $row['id'], '"', ($row['selected'] ? ' selected="selected"' : ''), '>
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
        <input type="hidden" name="sa" value="reservednames" />
        <input type="hidden" name="sa" value="add_file">
    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
    <input type="hidden" name="', $context['add_file_token_var'], '" value="', $context['add_file_token'], '" />
    </div>
</div>
    
    </form>';
}
