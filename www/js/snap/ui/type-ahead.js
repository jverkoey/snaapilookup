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

  var t = this;
  this._elements.small_logo
    .click(function() {
      t._hide_iframe();
    });

  this._current_value = '';

  // filters: #language or #framework
  // all: everything else
  this._database = {filters: {}, all: []};

  this._id_to_category = {};
  this._id_to_type = {};

  this._active_filters = [];

  this._list = null;
  this._selection = -1;

  this._active_function = null;
  this._function_cache = {};

  /**
   * ['category,id':{loading:true/false}]     loading = true -> an async request is active
   */
  this._hierarchy_request_queue = {};

  /**
   * [category][id] => hierarchy name
   */
  this._hierarchy_cache = {};

  this._ancestry_timer = null;
  this._hierarchy_timer = null;

  this._query_timer = null;
  this._active_search = null;
  this._cached_query_results = null;

  this._has_changed_since_selection = null;

  this._displaying_frame = false;
  this._frame_url = null;

  $.ajax({
    type    : 'GET',
    url     : '/js/static/data.js?'+Revisions.static_js_build,
    dataType: 'json',
    success : this._receive_data.bind(this),
    failure : this._fail_to_receive_data.bind(this)
  });

  if( window.sel ) {
    if( undefined == this._hierarchy_cache[window.sel.category] ) {
      this._hierarchy_cache[window.sel.category] = {};
    }

    var key = window.sel.category+','+window.sel.hierarchy;
    if( undefined == this._hierarchy_cache[window.sel.category][window.sel.hierarchy] &&
        undefined == this._hierarchy_request_queue[key] ) {
      this._hierarchy_request_queue[key] = {loading: false};
      if( this._ancestry_timer ) {
        clearTimeout(this._ancestry_timer);
      }
      this._ancestry_timer = setTimeout(this._request_ancestries.bind(this), 100);
    }

    this._elements.input.val(window.sel.name);
    this._display_function(window.sel);
  }
};

Snap.TypeAhead.prototype = {

  key : {
    enter : 13,
    escape: 27,
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

    var keydown_type = 'keypress';
    if( $.browser.safari ) {
      keydown_type = 'keydown';
    }

    if( event.type == 'keyup' || event.type == keydown_type ) {
      var new_val = this._elements.input.val();
      if( this._current_value != new_val ) {
        this._current_value = new_val;
        this._elements.dropdown.fadeIn('fast');
        this._cached_query_results = null;
        this._update_filter();
        this._has_changed_since_selection = true;
      }
    }

    if( event.type == keydown_type && this._list ) {
      if( event.keyCode == this.key.enter ) {
        var do_nothing = false;
        var selection = this._list[this._selection];
        if( !this._has_changed_since_selection && selection.function_id ) {
          // Go to this function's URL if we can.
          if( undefined != this._function_cache[selection.category] &&
              undefined != this._function_cache[selection.category][selection.function_id] ) {
            var function_info = this._function_cache[selection.category][selection.function_id];
            if( function_info.url ) {
              this._show_iframe(function_info.url);
            } else {
              function_info.navigate_immediately = true;
              this._render_function();
            }
            do_nothing = true;
          }
        }
        if( !do_nothing ) {
          this._handle_selection(this._selection);
        }
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

    if( event.keyCode == this.key.escape && this._displaying_frame ) {
      this._hide_iframe();
    }

    if( event.keyCode == this.key.down ||
        event.keyCode == this.key.up ) {
      event.stopPropagation();
      return false;
    }

    return true;
  },

  _handle_selection : function(index) {
    this._has_changed_since_selection = false;

    this._selection = index;
    var selection = this._list[this._selection];

    this._current_value = selection.name;
    this._elements.input.val(selection.name);

    if( selection.filter_id ) {
      if( undefined == this._active_filters[selection.type] ) {
        this._active_filters[selection.type] = {};
      }
      this._active_filters[selection.type][selection.filter_id] = selection.name;

      this._save_active_filters();

      this._render_filters();
      this.clear();
    } else if( selection.function_id ) {
      this._elements.dropdown.fadeOut('fast');

      this._display_function(selection);

      this._hide_iframe();
    } else {
      this._elements.dropdown.fadeOut('fast');
    }
  },

  _flatten_filters : function() {
    var flat_filters = [];
    for( var filter_type in this._active_filters ) {
      var filter = this._active_filters[filter_type];
      for( var filter_id in filter ) {
        flat_filters.push(filter_id);
      }
    }
    return flat_filters.join(',');
  },

  _save_active_filters : function() {
    $.cookie('filters', this._flatten_filters());
  },

  _display_function : function(selection) {
    if( undefined == this._function_cache[selection.category] ) {
      this._function_cache[selection.category] = {};
    }

    if( undefined == this._function_cache[selection.category][selection.function_id] ) {
      this._function_cache[selection.category][selection.function_id] = {
        name            : selection.name,
        type            : selection.type,
        category        : selection.category,
        hierarchy       : selection.hierarchy,
        id              : selection.function_id,
        loading         : true,
        loading_social  : true
      };
      $.ajax({
        type    : 'GET',
        url     : '/function',
        dataType: 'json',
        data    : {
          category  : selection.category,
          id        : selection.function_id
        },
        success : this._receive_function.bind(this),
        failure : this._fail_to_receive_function.bind(this)
      });

      $.ajax({
        type    : 'GET',
        url     : '/function/social',
        dataType: 'json',
        data    : {
          category  : selection.category,
          id        : selection.function_id
        },
        success : this._receive_social.bind(this),
        failure : this._fail_to_receive_social.bind(this)
      });
    }

    this._active_function = this._function_cache[selection.category][selection.function_id];

    this._displaying_frame = false;
    this._render_function();
  },

  _update_filter : function() {
    var trimmed_value = $.trim(this._current_value);
    if( trimmed_value == '' ) {
      this._elements.dropdown.html('<div class="empty"><b>Tip: Use # to filter by languages or frameworks.</b></div>');
    } else {
      var results = [];
      var hash_results = {};

      var MAX_RESULTS = 10;

      if( this._cached_query_results ) {
        var query = trimmed_value;
        var query_results = this._cached_query_results;
        for( var i = 0; i < query_results.length && results.length < MAX_RESULTS; ++i ) {
          var offset = query_results[i].n.toLowerCase().indexOf(query.toLowerCase());
          if( offset >= 0 ) {
            var entry = {
              type      : this._id_to_category[query_results[i].c] || 'Loading...',
              category  : query_results[i].c,
              hierarchy : query_results[i].h,
              function_id : query_results[i].i,
              name      : query_results[i].n,
              matches   : [{word: query, offset: offset, size: query.length}],
              score     : query.length * 100 / query_results[i].n.length * (offset == 0 ? 2 : 1)
            };
            var unique_id = 'query'+i;
            hash_results[unique_id] = entry;
            results.push(unique_id);
          }
        }

      } else if( trimmed_value[0] == '#' ) {
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
                    name      : filter.data[i2].name,
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
      } else {
        if( this._query_timer ) {
          clearTimeout(this._query_timer);
          this._query_timer = null;
        }
        this._query_timer = setTimeout(this._execute_query.bind(this, trimmed_value), 10);
        return;
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

      if( results.length > 0 ) {
        this._list = [];
        for( var i = 0; i < results.length; ++i ) {
          this._list.push(hash_results[results[i]]);
        }
        this._selection = 0;
      } else {
        this._list = null;
        this._selection = -1;
      }

      this._render_selection();
    }
  },

  _render_selection : function() {
    // Render the html.
    if( this._list ) {
      var html = [];
      for( var i = 0; i < this._list.length; ++i ) {
        var entry = this._list[i];
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
        if( i == this._selection ) {
          html.push(' selected');
        }
        html.push('">'+name+' <span class="category">'+entry.type+'</span>');

        var lineage = this._render_lineage(entry.category, entry.hierarchy, false);
        if( lineage ) {
          html.push(' <span class="lineage">'+lineage+'</span>');
        }

        html.push('</div>');
      }

      this._elements.dropdown.html(html.join(''));
      var t = this;
      this._elements.dropdown.children('.result').each(function(index) {
        $(this).click(function() {
          t._handle_selection.bind(t)(index);
        });
      });
    } else {
      this._elements.dropdown.html('<div class="empty">Bummer, we don\'t have an entry for that.</div>');
    }
  },

  _render_lineage : function(category, hierarchy, with_links) {
    if( undefined != this._hierarchy_cache[category] &&
        undefined != this._hierarchy_cache[category][hierarchy] ) {
      var info = this._hierarchy_cache[category][hierarchy];
      if( undefined != info.name ) {
        var lineage = [];
        var missing_any = false;
        if( info.ancestors ) {
          for( var i2 = 0; i2 < info.ancestors.length; ++i2 ) {
            var ancestor = info.ancestors[i2];
            if( undefined == this._hierarchy_cache[category][ancestor] ||
                undefined == this._hierarchy_cache[category][ancestor].name ) {
              missing_any = true;
              break;
            }
          }

          if( !missing_any ) {
            for( var i2 = 0; i2 < info.ancestors.length; ++i2 ) {
              var ancestor = this._hierarchy_cache[category][info.ancestors[i2]];
              var step = '';
              if( with_links ) {
                step += '<a class="external" href="'+ancestor.source_url+'">';
              }
              step += ancestor.name;
              if( with_links ) {
                step += '</a>';
              }
              lineage.push(step);
            }
          }
        }
        if( !missing_any ) {
          var step = '';
          if( with_links ) {
            step += '<a class="external" href="'+info.source_url+'">';
          }  
          step += info.name;
          if( with_links ) {
            step += '</a>';
          }
          lineage.push(step);
          return lineage.join(' &raquo; ');
        }
      }
    }

    return null;
  },

  _execute_query : function(query) {
    if( this._active_search ) {
      this._active_search.abort();
    }
    this._active_search = $.ajax({
      type    : 'GET',
      url     : '/search',
      dataType: 'json',
      query   : query,
      data    : {
        query   : query,
        filters : this._flatten_filters()
      },
      success : this._receive_search.bind(this),
      failure : this._fail_to_receive_search.bind(this)
    });
  },

  _receive_search : function(result, textStatus) {
    this._active_search = null;
    if( result.s ) {
      if( result.q == $.trim(this._current_value) ) {
        this._cached_query_results = result.r;

        for( var i = 0; i < result.r.length; ++i ) {
          var category = result.r[i].c;
          var hierarchy = result.r[i].h;
          if( undefined == this._hierarchy_cache[category] ) {
            this._hierarchy_cache[category] = {};
          }

          var key = category+','+hierarchy;
          if( undefined == this._hierarchy_cache[category][hierarchy] &&
              undefined == this._hierarchy_request_queue[key]) {
            this._hierarchy_request_queue[key] = {loading: false};
            if( this._ancestry_timer ) {
              clearTimeout(this._ancestry_timer);
            }
            this._ancestry_timer = setTimeout(this._request_ancestries.bind(this), 100);
          }
        }

        this._update_filter();
      }
    } else {
      this._cached_query_results = null;
    }
  },

  _fail_to_receive_search : function(result, textStatus) {
    this._active_search = null;
  },

  _request_ancestries : function() {
    this._ancestry_timer = null;
    var query = [];
    for( var key in this._hierarchy_request_queue ) {
      if( !this._hierarchy_request_queue[key].loading ) {
        query.push(key);
        this._hierarchy_request_queue[key].loading = true;
      }
    }
    if( query.length > 0 ) {
      $.ajax({
        type    : 'GET',
        url     : '/hierarchy',
        dataType: 'json',
        data    : {
          query  : query.join('|')
        },
        success : this._receive_ancestries.bind(this),
        failure : this._fail_to_receive_ancestries.bind(this)
      });
    }
  },

  _receive_ancestries : function(result, textStatus) {
    // Now that we've received the hierarchy for this item, load its ancestors (if necessary).
    if( result.succeeded ) {
      for( var category in result.ancestors ) {
        var ids = result.ancestors[category];
        for( var id in ids ) {
          var ancestors = ids[id];
          if( undefined == this._hierarchy_cache[category][id] ) {
            this._hierarchy_cache[category][id] = {};
          }
          this._hierarchy_cache[category][id].ancestors = ancestors;
          for( var i = 0; i < ancestors.length; ++i ) {
            if( undefined == this._hierarchy_cache[category][ancestors[i]] ) {
              this._hierarchy_cache[category][ancestors[i]] = {};
            }
          }
          delete this._hierarchy_request_queue[category+','+id];
        }
      }
    }

    if( this._hierarchy_timer ) {
      clearTimeout(this._hierarchy_timer);
      this._hierarchy_timer = null;
    }
    this._hierarchy_timer = setTimeout(this._request_hierarchy.bind(this), 100);
  },

  _fail_to_receive_ancestries : function(result, textStatus) {
  },

  _request_hierarchy : function() {
    var query = [];
    for( var category in this._hierarchy_cache ) {
      var ids = this._hierarchy_cache[category];
      for( var id in ids ) {
        var hierarchy = ids[id];
        if( undefined == hierarchy.name && !hierarchy.loading ) {
          hierarchy.loading = true;
          query.push(category+','+id);
        }
      }
    }

    if( query.length > 0 ) {
      $.ajax({
        type    : 'GET',
        url     : '/hierarchy/info',
        dataType: 'json',
        data    : {
          query  : query.join('|')
        },
        success : this._receive_hierarchies.bind(this),
        failure : this._fail_to_receive_hierarchies.bind(this)
      });
    }
  },

  _receive_hierarchies : function(result, textStatus) {
    if( result.succeeded ) {
      for( var category in result.info ) {
        var ids = result.info[category];
        for( var id in ids ) {
          var info = ids[id];
          if( undefined == this._hierarchy_cache[category][id] ) {
            this._hierarchy_cache[category][id] = {};
          }
          for( var key in info ) {
            this._hierarchy_cache[category][id][key] = info[key];
          }
          this._hierarchy_cache[category][id].loading = false;
        }
      }
      this._render_selection();
      this._render_function();
    }
    
  },

  _fail_to_receive_hierarchies : function(result, textStatus) {
  },

  _render_function : function() {
    if( !this._active_function || this._displaying_frame ) {
      return;
    }
    var html = [];
    html.push('<div class="function"><span class="name">');

    if( this._active_function.url ) {
      html.push('<a class="external" href="',this._active_function.url,'">');
    }
    html.push(this._active_function.name);
    if( this._active_function.url ) {
      html.push('</a>');
    }

    html.push('</span><span class="category">',
      this._active_function.type,
      '</span>');

    var category = this._active_function.category;
    var hierarchy = this._active_function.hierarchy;
    var lineage = this._render_lineage(category, hierarchy, true);
    if( lineage ) {
      html.push(' <span class="lineage">',lineage,'</span>');
    }

    html.push('</div>');

    var permalink = '/'+this._active_function.type+'/'+this._active_function.name;
    html.push('<div class="source"><a href="',permalink,'">snaapi.com',permalink,'</a></div>');

    if( this._active_function.short_description ) {
      html.push('<div class="short-description">',this._active_function.short_description,'</div>');
    }

    if( this._active_function.data ) {
      switch( this._id_to_category[this._active_function.category] ) {
        case 'PHP':
          html.push('<div class="signature">',this._active_function.data,'</div>');
          break;
        case 'CSS':
          html.push('<div class="row"><span class="title">Default value:</span><div class="value">',this._active_function.data.d,'</div></div>');
          html.push('<div class="row"><span class="title">Expected values:</span>');
          var values = this._active_function.data.v;
          for( var i = 0; i < values.length; ++i ) {
            html.push('<div class="value">');
            if( typeof values[i] == 'string' ) {
              html.push(values[i]);
            } else {
              html.push('<span class="value_name">',values[i].n,'</span><span class="description">',values[i].d,'</span>');
            }
            html.push('</div>');
          }
          html.push('</div>');
          html.push('<div class="row"><span class="title">Inherited:</span><div class="value">',this._active_function.data.i,'</div></div>');
          break;
      }
    }

    if( this._active_function.navigate_immediately ) {
      html.push('<div class="loading">Just a sec, once we\'ve loaded the details we\'ll send you off...</div>');
    } else if( this._active_function.loading ) {
      html.push('<div class="loading">Loading function details...</div>');
    }

    if( this._active_function.loading_social ) {
      html.push('<div class="social">Loading snaapits...</div>');
    } else if( this._active_function.social.length > 0 ) {
      html.push('<div class="social">');
      html.push('<div class="header">Suggested information</div>');
      var social = this._active_function.social;
      for( var i = 0; i < social.length; ++i ) {
        html.push('<div class="row">');
        html.push('<div class="box"><div class="score">',social[i].score,'</div>');
        html.push('<div class="ratings"><span class="rater up">+</span><span class="rater down">-</span></div></div>');
        html.push('<div class="data">');
        if( social[i].type == 'link' ) {
          html.push('<div class="link"><a href="',social[i].data,'">',social[i].data,'</a></div>');
        }
        html.push('</div>');
        html.push('</div>');
      }
      html.push('</div>');
    }

    if( undefined != window.user_id ) {
      html.push('<div class="socialness"><div class="methods"><ul>');
      html.push('<li>Add a link</li>');
  //    html.push('<li>Add a snippet</li>');
      html.push('</ul></div>');
      // Add a link
      html.push('<div class="form" style="display:none"><form method="post" action="/function/addurl"><input type="hidden" name="category" value="',this._active_function.category,'" /><input type="hidden" name="id" value="',this._active_function.id,'" /><label for="url">URL:</label><input type="text" class="text" name="url" id="url" size="50" value="" /><input type="submit" class="button" value="add" /></form></div>');
      // Add a snippet
      html.push('<div class="form" style="display:none">Add a snippet!</div>');
      html.push('</div>');
    }

    this._elements.external.hide();
    this._elements.result
      .html(html.join(''))
      .fadeIn('fast');

    new Snap.GhostInput(this._elementIDs.result+' .form:eq(0) .text', 'Web address');

    var methods = [
      // Add a link
      function() {
        $(this._elementIDs.result+' .form:eq(0) .text').focus();
      }.bind(this),

      // Add a snippet
      function() {
        
      }.bind(this)
    ];

    // Add a link.
    var t = this;
    
    if( undefined != window.user_id ) {
      $(this._elementIDs.result + ' li').each(function(index) {
        $(this).click(function() {
          $(t._elementIDs.result+' .form:not(:eq('+index+'))').fadeOut('fast', function() {
            $(t._elementIDs.result+' .form:eq('+index+')').fadeIn('fast');
            methods[index]();
          });
        });
      });
    }

    if( !this._active_function.loading_social ) {
      var fun = this._active_function;
      var t = this;

      $(this._elementIDs.result + ' .up').each(function(index) {
        $(this).click(function() {
          $.ajax({
            type    : 'POST',
            url     : '/function/vote',
            dataType: 'json',
            data    : {
              category  : fun.category,
              id        : fun.id,
              index     : social[index].ix,
              score     : social[index].score,
              vote      : 1
            },
            success : t._receive_vote_update.bind(t),
            failure : t._fail_to_receive_vote_update.bind(t)
          });
        });
      });

      $(this._elementIDs.result + ' .down').each(function(index) {
        $(this).click(function() {
          $.ajax({
            type    : 'POST',
            url     : '/function/vote',
            dataType: 'json',
            data    : {
              category  : fun.category,
              id        : fun.id,
              index     : social[index].ix,
              score     : social[index].score,
              vote      : -1
            },
            success : t._receive_vote_update.bind(t),
            failure : t._fail_to_receive_vote_update.bind(t)
          });
        });
      });
    }

    var t = this;
    $(this._elementIDs.result + ' a.external').click(function() {
      t._show_iframe($(this).attr('href'));
      return false;
    });
  },

  _receive_vote_update : function(result, textStatus) {
    if( result.succeeded ) {
      if( result.updated ) {
        $.ajax({
          type    : 'GET',
          url     : '/function/social',
          dataType: 'json',
          data    : {
            category  : result.category,
            id        : result.id
          },
          success : this._receive_social.bind(this),
          failure : this._fail_to_receive_social.bind(this)
        });
      }
    }
  },

  _fail_to_receive_vote_update : function(result, textStatus) {
    
  },

  _show_iframe : function(url) {
    if( !this._displaying_frame || this._frame_url != url ) {
      this._displaying_frame = true;
      this._frame_url = url;
      this._elements.logo.fadeOut('fast');
      this._elements.catch_phrase.fadeOut('fast');
      this._elements.filters.fadeOut('fast');
      this._elements.small_logo.fadeIn('fast');
      $('body').css({overflow:'hidden'});
      $('#footer').hide();
      this._elements.external_table.css({position:'absolute'});
      this._elements.result.fadeOut('fast', function() {
        this._elements.external
          .html('<div id="eww">Eww, frames</div><iframe src="'+url+'"></iframe>');
        this._elements.external.show();
      }.bind(this));
    }
  },

  _hide_iframe : function(url) {
    this._displaying_frame = false;
    this._elements.logo.fadeIn('fast');
    this._elements.catch_phrase.fadeIn('fast');
    $('body').css({overflow:'visible'});
    $('#footer').show();
    this._elements.external_table.css({position:'static'});
    for( var filter in this._active_filters ) {
      this._elements.filters.fadeIn('fast');
      break;
    }
    this._elements.small_logo.fadeOut('fast');
    this._elements.external.fadeOut('fast', function() {
      this._elements.result.show();
    }.bind(this));
  },

  _receive_social : function(result, textStatus) {
    if( result.succeeded ) {
      var category = result.category;
      var id = result.id;

      var function_info = this._function_cache[category][id];

      function_info.social = result.data;
      function_info.loading_social = false;
/*
      if( function_info.data ) {
        switch( this._id_to_category[function_info.category] ) {
          case 'PHP':
            function_info.data = function_info.data.replace(/<\/s>/g, '</span>');
            function_info.data = function_info.data.replace(/<st>/g, '<span class="type">');
            function_info.data = function_info.data.replace(/<si>/g, '<span class="initializer">');
            function_info.data = function_info.data.replace(/<sm>/g, '<span class="methodname">');
            function_info.data = function_info.data.replace(/<smp>/g, '<span class="methodparam">');
            function_info.data = function_info.data.replace(/<sp>/g, '<span class="methodarg">');
            break;
        }
      }*/
      if( this._active_function &&
          this._active_function.category == result.category &&
          this._active_function.id == result.id ) {
        this._render_function();
      }
    }
  },

  _fail_to_receive_social : function(result, textStatus) {
    
  },

  _receive_function : function(result, textStatus) {
    if( result.succeeded ) {
      var category = result.category;
      var id = result.id;

      var function_info = this._function_cache[category][id];
      if( function_info.navigate_immediately && result.data.url ) {
        this._show_iframe(result.data.url);
        return;
      }

      for( var key in result.data ) {
        function_info[key] = result.data[key];
      }
      function_info.loading = false;

      if( function_info.data ) {
        switch( this._id_to_category[function_info.category] ) {
          case 'PHP':
            function_info.data = function_info.data.replace(/<\/s>/g, '</span>');
            function_info.data = function_info.data.replace(/<st>/g, '<span class="type">');
            function_info.data = function_info.data.replace(/<si>/g, '<span class="initializer">');
            function_info.data = function_info.data.replace(/<sm>/g, '<span class="methodname">');
            function_info.data = function_info.data.replace(/<smp>/g, '<span class="methodparam">');
            function_info.data = function_info.data.replace(/<sp>/g, '<span class="methodarg">');
            break;
          case 'CSS':
            function_info.data = window["eval"]("(" + function_info.data + ")");
            if( !this._active_function.data.d ) {
              this._active_function.data.d = 'Not defined';
            }
            if( !this._active_function.data.i ) {
              this._active_function.data.i = 'No';
            }
            break;
        }
      }
      if( this._active_function &&
          this._active_function.category == result.category &&
          this._active_function.id == result.id ) {
        this._render_function();
      }
    }
  },

  _fail_to_receive_function : function(result, textStatus) {
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

        t._save_active_filters();
        t._render_filters();
      });
    } else {
      this._elements.filters.hide();
    }
  },

  _receive_data : function(result, textStatus) {
    this._database.filters = result;

    // Compile the data into an id=>category map for quick access.
    for( var i = 0; i < result.length; ++i ) {
      var data = result[i].data;
      for( var i2 = 0; i2 < data.length; ++i2 ) {
        var item = data[i2];
        this._id_to_category[item.id] = item.name;
        this._id_to_type[item.id] = result[i].type;
      }
    }

    if( window.sel ) {
      if( undefined == this._active_filters[window.sel.filter_type] ) {
        this._active_filters[window.sel.filter_type] = {};
      }
      this._active_filters[window.sel.filter_type][window.sel.category] = this._id_to_category[window.sel.category];

      this._render_filters();
    } else if( $.cookie('filters') ) {
      var filters = $.cookie('filters').split(',');
      for( var i = 0; i < filters.length; ++i ) {
        var type = this._id_to_type[filters[i]];
        if( undefined == this._active_filters[type] ) {
          this._active_filters[type] = {};
        }
        this._active_filters[type][filters[i]] = this._id_to_category[filters[i]];
      }
      this._render_filters();
    }

    this._update_filter();
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
