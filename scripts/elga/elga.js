;(function($){
    $(document).ready(function(){
        var $select = $('div.elga-select-cats form select');
        $select.on("click", function(){
            if (this.length === 1 && ( ! arguments.callee.loadCats ) ) {
                var $first = $select.find(":selected"),
                      selecttext = $first.text();
                $first.text('Loading');
                arguments.callee.loadCats = 1;
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
    });
})(jQuery);
