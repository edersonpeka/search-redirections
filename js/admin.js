search_redirections.recalculate_indexes = function () {
    jQuery( '.-sr-group-name-input' ).each( function ( _index, _input ) {
        var _tr = jQuery( _input ).closest( 'tr' );
        jQuery( [ 'group', 'term', 'dest' ] ).each( function ( _f, _field ) {
            _tr.find( 'input[name$="[' + _field + ']"]' ).attr(
                'name',
                'search_redirections_rules[' + _index + '][' + _field + ']'
            );
        } );
    } );
}
search_redirections.ask_confirm = function ( _e ) {
    if ( search_redirections.needs_confirm ) {
        if ( _e.originalEvent ) _e.originalEvent.returnValue = search_redirections.confirm_unload;
        return search_redirections.confirm_unload;
    }
}
jQuery( document ).ready( function () {
    search_redirections.needs_confirm = false; 
    window.onbeforeunload = search_redirections.ask_confirm;
    jQuery( 'form.search-redirections-options' ).on( 'change', function () {
        search_redirections.needs_confirm = true;
    } ).on( 'submit', function () {
        search_redirections.needs_confirm = false;
    } );

    var _table_groups = jQuery( '.search-redirections-groups' );
    var _tbody = jQuery( 'tbody', _table_groups );
    jQuery( 'tr.-sr-rules input[type="text"].-sr-group-name-input', _tbody ).attr( 'type', 'hidden' );
    _tbody.on( 'change', 'input[type="text"].-sr-group-name-input', function () {
        var _group_name = jQuery( this ).val();
        var _tr_group = jQuery( this ).closest( 'tr.-sr-group', _tbody );
        var _iter_tr = _tr_group;
        while ( _iter_tr.next().hasClass( '-sr-rules' ) ) {
            _iter_tr = _iter_tr.next();
            jQuery( '.-sr-group-name-input', _iter_tr ).val( _group_name );
        }
    } );
    _table_groups.on( 'click', '.button', function () {
        var _terms = jQuery( 'td.-sr-term', _table_groups );
        var _t = _terms.length;
        var _button = jQuery( this );

        var _tr_button = _button.closest( 'tr', _table_groups );

        if ( _button.hasClass( '-sr-add-group' ) ) {
            var _tr = '\
                    <tr class="-sr-group">\
                        <td>\
                            <input type="text" name="search_redirections_rules[' + _t + '][group]" class="-sr-group-name-input" />\
                        </td>\
                        <td class="-sr-term">\
                            <input type="text" name="search_redirections_rules[' + _t + '][term]" />\
                        </td>\
                        <td>\
                            <input type="text" name="search_redirections_rules[' + _t + '][dest]" size="35" />\
                        </td>\
                        <td></td>\
                    </tr>\
                    <tr class="search-redirection-rule-buttons">\
                        <td>\
                            <a href="#" class="button button-link-delete -sr-remove-group">\
                                <span class="dashicons dashicons-minus"></span>\
                                <span>' + search_redirections.remove_group + '</span>\
                            </a>\
                        </td>\
                        <td colspan="2">\
                            <a href="#" class="button button-secondary -sr-add-rule">\
                                <span class="dashicons dashicons-plus"></span>\
                                <span>' + search_redirections.add_rule + '</span>\
                            </a>\
                        </td>\
                        <td></td>\
                    </tr>\
                    ';
            _tbody.append( _tr );
            jQuery( 'input[type="text"].-sr-group-name-input', _tbody ).last().focus();
            return false;
        }
        if ( _button.hasClass( '-sr-remove-group' ) ) {
            if ( confirm( search_redirections.confirm_removal ) ) {
                var _tr_button = _button.closest( 'tr' );
                while( _tr_button.prev().hasClass( '-sr-rules' ) ) {
                    _tr_button.prev().remove();
                }
                while( _tr_button.prev().hasClass( '-sr-group' ) ) {
                    _tr_button.prev().remove();
                }
                _tr_button.remove();
                search_redirections.recalculate_indexes();
            }
            return false;
        }
        if ( _button.hasClass( '-sr-add-rule' ) ) {
            _group_name = _tr_button.prev().find( 'input.-sr-group-name-input' ).val();
            var _rule_tr = jQuery( '\
                <tr class="-sr-rules">\
                    <td class="-sr-term">\
                        <input type="hidden" name="search_redirections_rules[' + _t + '][group]" class="-sr-group-name-input" />\
                    </td>\
                    <td>\
                        <input type="text" name="search_redirections_rules[' + _t + '][term]" />\
                    </td>\
                    <td>\
                        <input type="text" name="search_redirections_rules[' + _t + '][dest]" size="35" />\
                    </td>\
                    <td>\
                        <a href="#" class="button button-link-delete -sr-remove-rule">\
                            <span class="dashicons dashicons-minus"></span>\
                        </a>\
                    </td>\
                </tr>\
                ' );
            jQuery( '.-sr-group-name-input', _rule_tr ).val( _group_name );
            jQuery( 'a.-sr-remove-rule', _rule_tr ).attr( 'title', search_redirections.remove_rule );
            _tr_button.before( _rule_tr );
            return false;
        }
        if ( _button.hasClass( '-sr-remove-rule' ) ) {
            var _should_remove = true;
            jQuery( 'input[type="text"]', _tr_button ).each( function () {
                if ( jQuery( this ).val() ) {
                    _should_remove = false;
                }
            } );
            if ( !_should_remove ) _should_remove = confirm( search_redirections.confirm_removal );
            if ( _should_remove ) {
                _tr_button.remove();
                search_redirections.recalculate_indexes();
            }
            return false;
        }
    } );
} );