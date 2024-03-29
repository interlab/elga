;(function($){
    //var repeat = function(s, n) { return new Array(n + 1).join(s) };
    $(document).ready(function(){
        // var i = 0;
        // console.log(elgaimgload.src);
        $('.elga-scroll').jscroll({
            autoTriggerUntil: 100,
            loadingHtml: '<i class="fa fa-spinner fa-pulse"></i> Loading...',
            padding: 20,
            nextSelector: 'a.jscroll-next:last',
            contentSelector: '',
            callback: function(){
                jQuery("div.jscroll-added").children().not("div.jscroll-next-parent").appendTo("div.elga-thumbs");
                // i++;
                // console.log(i + 'test jscroll');
            },
        });

        var $select = $('div.elga-select-cats form select');
        $select.on("click", function loadCats(){
            if (this.length === 1 && ( ! loadCats.isLoaded ) ) {
                var $first = $select.find(":selected"),
                      selecttext = $first.text();
                $first.text('Loading');
                loadCats.isLoaded = 1;
                $.getJSON(elk_scripturl + "?action=gallery;sa=ajax;m=loadcats", function(data) {
                    data.result.forEach(function(val){
                        $select.append( $('<option value="' + val.id + '">' + ' ' + php_str_repeat('-', val.depth) + ' ' + val.name + '</option>') );
                    });
                })
                // .done( () => { console.log( "second success" ); } );
                // .fail( () => { console.log( "error" ); } );
                .always(function() {
                    $first.text(selecttext);
                });
            }
        });
        $select.on("change", function(){
            var selval = $select.val();
            if (selval > 0) {
                window.location = elk_scripturl + '?action=gallery;sa=album;id=' + selval;
            }
        });

        var $sortselect = $('div.elga-sort-fields select');
        $sortselect.on("change", function(){
            var selval = $sortselect.val();
            var query = window.location.search;
            // var album_id = $sortselect.parent().find('input[name=album_id]').val();
            var q = query.match(/;(?:album|id)=(\d+)/);
            var album_id = q ? q[1] : 0;
            
            q = query.match(/;sa=([\w_]+)/);
            var sa = q ? q[1] : 'album';

            q = query.match(/;user=(\d+)/);
            var user = q ? q[1] : 0;

            if (selval !== 0) {
                window.location = elk_scripturl + '?action=gallery;sa=' + sa + (sa == 'album' ? ';id=' + album_id : ';album=' + album_id) + ';user=' + user +  ';sort=' + selval;
            }
        });

        function copyTxt(){
            var res = '';
            // Use try & catch for unsupported browser
            try {
                // The important part (copy selected text)
                var successful = document.execCommand('copy');
                if (successful) res = 'Copied!';
                else res = 'Unable to copy!';
            } catch (err) {
                res = 'Unsupported Browser!';
            }
            return res;
        }

        $("#elga-copy-btn").click(function(){
            var $btn = $(this);
            var $prev = $btn.prev();
            var val = $prev.val();
            $prev.select();
            var $answer = $btn.next("#elga-copy-answer");
            $answer.html('').show();
            $answer.html(copyTxt()).fadeOut(4000);
        });

    });
})(jQuery);
