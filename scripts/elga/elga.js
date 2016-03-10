;(function($){
    $(document).ready(function(){
        // var i = 0;
        // console.log(elgaimgload.src);
        $('.elga-scroll').jscroll({
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
                        $select.append( $('<option value="' + val.id + '">' + val.name + '</option>') );
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
