/**
 * A typeahead feature specializing in searching api data.
 *
 * @require-package core
 */

Snap.TypeAhead = function( elementIDs) {
  // input, filters, dropdown

  this._elementIDs = elementIDs;
  this._elements = {};
  for( var key in this._elementIDs ) {
    if( key != 'canvas' ) {
      this._elements[key] = $(this._elementIDs[key]);
    } else {
      this._elements[key] = $('#'+this._elementIDs[key]);
    }
  }

  this._elements.input
    .keydown(this._handle_key.bind(this))
    .keyup(this._handle_key.bind(this))
    .keypress(this._handle_key.bind(this))
    .focus(this._gain_focus.bind(this))
    .blur(this._lose_focus.bind(this));

  this._current_value = '';

  // filters: #language or #framework
  // all: everything else
  this._database = {filters: {}, all: []};

  this._active_filters = [];

  this._list = null;
  this._selection = -1;

  $.ajax({
    type    : 'GET',
    url     : '/js/static/data.js?'+Revisions.static_js_build,
    dataType: 'json',
    success : this._receive_data.bind(this),
    failure : this._fail_to_receive_data.bind(this)
  });
};

Snap.TypeAhead.prototype = {

  key : {
    enter : 13,
    left  : 37,
    up    : 38,
    right : 39,
    down  : 40
  },

  clear : function() {
    this._current_value = '';
    this._elements.input.val('');
    this._update_filter();
  },

  _handle_key : function(event) {
    if( event.type == 'keyup' || event.type == 'keypress' ) {
      var new_val = this._elements.input.val();
      if( this._current_value != new_val ) {
        this._current_value = new_val;
        this._elements.dropdown.fadeIn('fast');
        this._update_filter();
      }
    }

    if( event.type == 'keypress' && this._list ) {
      if( event.keyCode == this.key.enter ) {
        this._handle_selection(this._selection);
      } else {
        var old_selection = this._selection;
        if( event.keyCode == this.key.down ) {
          this._selection++;
        } else if( event.keyCode == this.key.up ) {
          this._selection--;
        }

        if( this._selection < 0 ) {
          this._selection = this._list.length - 1;
        } else if( this._selection >= this._list.length ) {
          this._selection = 0;
        }
        if( old_selection != this._selection ) {
          this._elements.dropdown.children('.selected').removeClass('selected');
          this._elements.dropdown.children('.result:eq('+this._selection+')').addClass('selected');
        }
      }
    }

    if( event.keyCode == this.key.down ||
        event.keyCode == this.key.up ) {
      event.stopPropagation();
      return false;
    }

    return true;
  },

  _handle_selection : function(index) {
    this._selection = index;
    var selection = this._list[this._selection];

    this._current_value = selection.name;
    this._elements.input.val(selection.name);

    if( selection.filter_id ) {
      if( undefined == this._active_filters[selection.type] ) {
        this._active_filters[selection.type] = {};
      }
      this._active_filters[selection.type][selection.filter_id] = selection.name;

      this._render_filters();
      this.clear();
    } else {
      this._elements.dropdown.fadeOut('fast');
    }
    // selection has the rest of the juicy bits
  },

  _update_filter : function() {
    var trimmed_value = $.trim(this._current_value);
    if( trimmed_value == '' ) {
      this._elements.dropdown.html('<div class="empty"><b>Tip: Use # to filter by languages or frameworks.</b></div>');
    } else {
      var results = [];
      var hash_results = {};

      var MAX_RESULTS = 10;

      if( trimmed_value[0] == '#' ) {
        var query = trimmed_value.substr(1);
        // We're searching filters.
        if( query.length > 0 ) {
          if( this._database.filters.length > 0 ) {
            var filters = this._database.filters;
            for( var i = 0; i < filters.length && results.length < MAX_RESULTS; ++i ) {
              var filter = filters[i];
              var active_filter = this._active_filters[filter.type];
              for( var i2 = 0; i2 < filter.data.length && results.length < MAX_RESULTS; ++i2 ) {
                if( undefined != active_filter && undefined != active_filter[filter.data[i2].id] ) {
                  continue;
                }
                var offset = filter.data[i2].name.toLowerCase().indexOf(query.toLowerCase());
                if( offset >= 0 ) {
                  var entry = {
                    type      : filter.type,
                    filter_id : filter.data[i2].id,
                    name      : '#'+filter.data[i2].name,
                    matches   : [{word: query, offset: offset, size: query.length}],
                    score     : query.length * 100 / filter.data[i2].name.length * (offset == 0 ? 2 : 1)
                  };
                  var unique_id = 'filter'+i+'-'+i2;
                  hash_results[unique_id] = entry;
                  results.push(unique_id);
                }
              }
            }
          } else {
            this._elements.dropdown.html('<div class="empty">We\'re still loading the filters, just a sec.</div>');
            return;
          }
        } else {
          this._elements.dropdown.html('<div class="empty">Type a language or framework name.</div>');
          return;
        }
      }

      // Calculate the score for each result.
      // Score = sum total of matched characters.
      for( var i = 0; i < results.length; ++i ) {
        var entry = hash_results[results[i]];
        if( undefined == entry.score ) {
          entry.score = 0;
          for( var i2 = 0; i2 < entry.matches.length; ++i2 ) {
            entry.score += entry.matches[i2].size;
          }
          entry.score /= entry.name.length;
        }
      }

      // Sort by score.
      function by(left, right) {
        var left_entry = hash_results[left];
        var right_entry = hash_results[right];
        return right_entry.score - left_entry.score;
      }
      results = results.sort(by);

      // Render the html.
      var html = [];
      for( var i = 0; i < results.length; ++i ) {
        var entry = hash_results[results[i]];
        var name = entry.name;

        var regex = [];
        for( var i2 = 0; i2 < entry.matches.length; ++i2 ) {
          var match = entry.matches[i2];
          regex.push(match.word);
        }

        name = name.gsub(new RegExp('('+regex.join('|').replace('+', '\\+')+')','i'), function(match) {
          return '<em>' + match[0] + '</em>';
        });

        html.push('<div class="result');
        if( i == 0 ) {
          html.push(' selected');
        }
        html.push('">'+name+' <span class="map-name">'+entry.type+'</span></div>');
      }
      if( html.length == 0 ) {
        this._elements.dropdown.html('<div class="empty">Bummer, we don\'t have an entry for that.</div>');
      } else {
        this._elements.dropdown.html(html.join(''));
        var t = this;
        this._elements.dropdown.children('.result').each(function(index) {
          $(this).click(function() {
            t._handle_selection.bind(t)(index);
          });
        });
      }

      if( results.length > 0 ) {
        this._list = [];
        for( var i = 0; i < results.length; ++i ) {
          var entry = hash_results[results[i]];
          delete entry.matches;
          delete entry.score;
          this._list.push(entry);
        }
        this._selection = 0;
      } else {
        this._list = null;
        this._selection = -1;
      }
    }
  },

  _render_filters : function() {
    var html = [];
    var any_filters = false;
    html.push('<div class="header">Filters</div><table><tbody><tr>');
    for( var filter_type in this._active_filters ) {
      var filter = this._active_filters[filter_type];
      html.push('<td class="filter"><span class="type">',filter_type,'</span>');
      for( var filter_id in filter ) {
        var item = filter[filter_id];
        html.push('<div class="item"><span id="',filter_type,'-',filter_id,'" title="Click to remove">',item,'</span></div>');
        any_filters = true;
      }
      html.push('</td>');
    }
    html.push('</tr></tbody></table>');
    if( any_filters ) {
      this._elements.filters
        .html(html.join(''))
        .show();

      var t = this;
      $(this._elementIDs.filters+' .filter .item span').click(function() {
        var filter_type = this.id.substr(0, this.id.indexOf('-'));
        var filter_id = this.id.substr(this.id.indexOf('-') + 1);

        delete t._active_filters[filter_type][filter_id];
        var any_filters_left = false;
        for( var key in t._active_filters[filter_type] ) {
          any_filters_left = true;
          break;
        }
        if( !any_filters_left ) {
          delete t._active_filters[filter_type];
        }

        t._render_filters();
      });
    } else {
      this._elements.filters.hide();
    }
  },

  _receive_data : function(result, textStatus) {
    this._database.filters = result;
  },

  _fail_to_receive_data : function(result, textStatus) {
  },

  _gain_focus : function() {
    this._current_value = this._elements.input.val();
    this._update_filter();

    this._elements.dropdown.fadeIn('fast');
    this._elements.input.select();
  },

  _lose_focus : function() {
    this._elements.dropdown.fadeOut('fast');
  }

};
