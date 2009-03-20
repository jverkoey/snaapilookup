/**
 * A typeahead feature specializing in searching api data.
 *
 * @require-package core
 * @requires database.js
 * @requires filter-bar.js
 */

Snap.TypeAhead = function(elementIDs, filterbar) {
  this._elementIDs = elementIDs;
  this._elements = {};
  for( var key in this._elementIDs ) {
    if( key != 'canvas' ) {
      this._elements[key] = $(this._elementIDs[key]);
    } else {
      this._elements[key] = $('#'+this._elementIDs[key]);
    }
  }
  this._filterbar = filterbar;

  this._elements.input
    .keydown(this._handle_key.bind(this))
    .keyup(this._handle_key.bind(this))
    .keypress(this._handle_key.bind(this))
    .focus(this._gain_focus.bind(this))
    .blur(this._lose_focus.bind(this));

  this._elements.goback.click(this._hide_iframe.bind(this));

  this._current_value = '';

  this._list = null;
  this._selection = -1;
  this._offset = 0;
  this._list_length = 10;

  this._active_function = null;

  this._hover_timer = null;

  this._has_changed_since_selection = null;

  this._displaying_frame = false;
  this._frame_url = null;
  this._db = Snap.Database.singleton;

  if( window.sel && window.sel.name ) {
    this._elements.input.val(window.sel.name);
    this._load_function(window.sel);
    this._display_function(window.sel);
  }

  this._db.register_callbacks({
    receive_categories    : this._receive_categories.bind(this),
    receive_function      : this._receive_function.bind(this),
    receive_social        : this._receive_function.bind(this),
    receive_hier          : this._receive_hier.bind(this),
    receive_hierarchy     : this._receive_hierarchy.bind(this),
    navigate_immediately  : this._navigate_immediately.bind(this)
  });
};

Snap.TypeAhead.prototype = {

  key : {
    enter     : 13,
    escape    : 27,
    page_up   : 33,
    page_down : 34,
    left      : 37,
    up        : 38,
    right     : 39,
    down      : 40
  },

  clear : function() {
    this._current_value = '';
    this._elements.input.val('');
    this._do_search();
  },

  _handle_key : function(event) {
    var keydown_type = 'keypress';
    if( $.browser.safari ) {
      keydown_type = 'keydown';
    }

    if( event.type == 'keyup' || event.type == keydown_type ) {
      var new_val = this._elements.input.val();
      if( this._current_value != new_val ) {
        if( this._hover_timer ) {
          clearTimeout(this._hover_timer);
        }
        this._hover_timer = setTimeout(this._hover.bind(this), 1000);
        this._current_value = new_val;
        this._elements.dropdown.fadeIn('fast');
        this._do_search();
        this._has_changed_since_selection = true;
      }
    }

    if( event.type == keydown_type && this._list ) {
      if( event.keyCode == this.key.enter ) {
        var do_nothing = false;
        var selection = this._list[this._selection];
        if( !this._has_changed_since_selection && this._active_function.id == selection.function_id ) {
          // Go to this function's URL if we can.
          if( this._db.is_function_cached(selection.category, selection.function_id) ) {
            var function_info = this._db.get_function(selection.category, selection.function_id);
            if( function_info.url ) {
              this._elements.dropdown.fadeOut('fast');
              this._show_iframe(function_info.url);
            } else {
              function_info.navigate_immediately = true;
              this._render_function();
            }
            do_nothing = true;
          }
        }
        if( !do_nothing ) {
          if( this._hover_timer ) {
            clearTimeout(this._hover_timer);
            this._hover_timer = null;
          }
          this._handle_selection(this._selection);
          if( selection.function_id ) {
            $.ajax({
              type    : 'POST',
              url     : '/function/select',
              data    : {
                category  : selection.category,
                id        : selection.function_id
              }
            });
          }
        }
      } else {
        var old_selection = this._selection;
        if( event.keyCode == this.key.down ) {
          this._selection++;
        } else if( event.keyCode == this.key.up ) {
          this._selection--;
        } else if( event.keyCode == this.key.page_up ) {
          this._selection -= this._list_length;
        } else if( event.keyCode == this.key.page_down ) {
          this._selection += this._list_length;
        }

        if( this._selection < 0 ) {
          this._selection = this._list.length - 1;
        } else if( this._selection >= this._list.length ) {
          this._selection = 0;
        }
        if( old_selection != this._selection ) {
          if( this._selection < this._offset ) {
            this._offset = this._selection;
            this._render_selection();
          } else if( this._selection >= this._offset + this._list_length ) {
            this._offset += this._selection - (this._offset + this._list_length) + 1;
            this._render_selection();
          } else {
            this._elements.dropdown.children('.holder').children('.selected').removeClass('selected');
            this._elements.dropdown.children('.holder').children('.result:eq('+(this._selection-this._offset)+')').addClass('selected');
          }
          if( this._hover_timer ) {
            clearTimeout(this._hover_timer);
          }
          this._hover_timer = setTimeout(this._hover.bind(this), 500);
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

  _hover : function() {
    if( this._list && this._selection >= 0 ) {
      var selection = this._list[this._selection];
      if( selection.function_id ) {
        this._load_function(this._list[this._selection]);
      }
    }
  },

  _handle_selection : function(index) {
    this._has_changed_since_selection = false;

    this._selection = index;
    var selection = this._list[this._selection];

    this._current_value = selection.name;
    this._elements.input.val(selection.name);

    if( selection.filter_id ) {
      this._filterbar.toggle(selection.type, selection.filter_id);
      this.clear();
    } else if( selection.function_id ) {
      this._elements.dropdown.fadeOut('fast');

      this._load_function(selection);
      if( !this._displaying_frame ) {
        this._display_function(selection);
      } else {
        this._display_function(selection, true);
        var function_info = this._db.get_function(selection.category, selection.function_id);
        if( function_info.url ) {
          this._show_iframe(function_info.url);
        } else {
          this._elements.messages.html('Just a sec, once we\'ve loaded the details we\'ll send you off...').fadeIn('fast');
          function_info.navigate_immediately = true;
        }
      }
    } else {
      this._elements.dropdown.fadeOut('fast');
    }
  },

  _load_function : function(selection) {
    if( !this._db.is_function_cached(selection.category, selection.function_id) ) {
      this._db.request_function(
        selection.category,
        selection.function_id,
        selection.name,
        selection.type,
        selection.hierarchy,
        false
      );
      this._db.ensure_hierarchy_loaded(selection.category, selection.hierarchy);
    }
  },

  _display_function : function(selection, invisible) {
    this._active_function = this._db.get_function(selection.category, selection.function_id);

    if( !invisible ) {
      this._displaying_frame = false;
    }
    this._render_function();
  },

  _do_search : function() {
    var trimmed_value = $.trim(this._current_value);
    if( trimmed_value == '' ) {
      this._elements.dropdown.html('<div class="empty"><b>Tip: Type # to filter by specific languages or frameworks.<br/>By default, all languages and frameworks are searched.</b></div>');
    } else {
      this._list = this._db.search(trimmed_value, this._filterbar._is_category_filtered);
      this._selection = this._list ? 0 : -1;
      this._offset = 0;

      if( this._list == -1 ) {
        this._elements.dropdown.html('<div class="empty">We\'re still loading the filters, just a sec.</div>');
        this._list = null;
        this._selection = -1;
      } else if( this._list == -2 ) {
        this._elements.dropdown.html('<div class="empty">Type a language or framework name.</div>');
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
      var end = Math.min(this._offset + this._list_length, this._list.length);
      var is_filter_list = false;
      for( var i = this._offset; i < end; ++i ) {
        var entry = this._list[i];
        var name = entry.name;

        var regex = [];
        for( var i2 = 0; i2 < entry.matches.length; ++i2 ) {
          var word = entry.matches[i2];
          if( word.length > 0 ) {
            regex.push(word);
          }
        }

        if( regex.length > 0 ) {
          name = name.gsub(new RegExp('('+regex.join('|')
            .replace('(', '\\(')
            .replace(')', '\\)')
            .replace('*', '\\*')
            .replace('$', '\\$')
            .replace('+', '\\+')+')','i'), function(match) {
            return '<em>' + match[0] + '</em>';
          });
        }

        html.push('<div class="result');
        if( i == this._selection ) {
          html.push(' selected');
        }
        html.push('">');
        if( entry.filter_id ) {
          is_filter_list = true;
          if( this._filterbar.is_filtered(entry.filter_id) ) {
            html.push('<span class="remove">remove</span> ');
          }
        }
        html.push(name+' <span class="category">'+entry.type+'</span>');

        if( undefined != entry.function_id ) {
          var lineage = this._render_lineage(entry.category, entry.hierarchy, false);
          if( lineage ) {
            html.push(' <span class="lineage">'+lineage+'</span>');
          }
        }

        html.push('</div>');
      }

      html.push('<div class="result_info">');

      if( this._list.length == 1 ) {
        html.push('The only entry');
      } else {
        html.push((this._offset+1),'-',(Math.min(this._list.length, this._offset+this._list_length)),' out of ',this._list.length);
      }
      html.push('</div>');

      this._elements.dropdown.html('<div class="holder">'+html.join('')+'</div>');
      var t = this;
      this._elements.dropdown.children('.holder').children('.result').each(function(index) {
        $(this).click(function() {
          t._handle_selection.bind(t)(index+t._offset);
        });
      });
    } else {
      this._elements.dropdown.html('<div class="empty">Bummer, we don\'t have an entry for that.</div>');
    }
  },

  _render_lineage : function(category, hierarchy, with_links) {
    if( this._db.hierarchies_loaded() ) {
      var info = this._db.get_hierarchy(category, hierarchy);
      var lineage = [];
      var missing_any = false;
      if( info.ancestors ) {
        for( var i2 = 0; i2 < info.ancestors.length; ++i2 ) {
          var ancestor = this._db.get_hierarchy(category, info.ancestors[i2]);
          var step = '';
          if( with_links && ancestor.source_url ) {
            step += '<a class="external" href="'+ancestor.source_url+'">';
          }
          step += ancestor.name;
          if( with_links ) {
            step += '</a>';
          }
          lineage.push(step);
        }
      }
      var step = '';
      if( with_links && info.source_url ) {
        step += '<a class="external" href="'+info.source_url+'">';
      }  
      step += info.name;
      if( with_links ) {
        step += '</a>';
      }
      lineage.push(step);
      return lineage.join(' &raquo; ');
    }

    return null;
  },

  _render_function : function() {
    if( !this._active_function ) {
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

    var pluralize = function(text, num, plural) {
      return num == 1 ? text : text+plural;
    }

    if( this._active_function.data ) {
      var data = this._active_function.data;
      switch( this._active_function.category ) {
        case 30:  // Firebug
        case 9:   // PHP
        case 28:  // django
        case 26:  // Zend
          html.push('<div class="signature">',data,'</div>');
          break;
        case 34:  // twitter
          html.push('<div class="row"><span class="title">URL:</span><div class="value"><a href="',data.u,'">',data.u,'</a></div></div>');
          html.push('<div class="row"><span class="title">',pluralize('Format', data.f.length, 's'),':</span><div class="value">',data.f.join(', '), '</div></div>');
          html.push('<div class="row"><span class="title">',pluralize('Method', data.m.length, 's'),':</span><div class="value">',data.m.join(', '), '</div></div>');
          html.push('<div class="row"><span class="title">API limit:</span><div class="value">',(data.l || 'Not applicable'), '</div></div>');
          if( data.p ) {
            html.push('<div class="row"><span class="title">Parameters:</span>');
            for( var name in data.p ) {
              var param = data.p[name];
              html.push('<div class="value"><span class="value_name">',name,'</span><span class="description">');
              if( param.o ) {
                html.push('<span class="optional">(Optional)</span>');
              }
              html.push(param.d,'</span></div>');
            }
            html.push('</div>');
          }
          if( data.r ) {
            html.push('<div class="row"><span class="title">Returns:</span><div class="value">',data.r, '</div></div>');
          }
          break;
        case 29:  // iPhone
          if( data.i && data.t ) {
            var types = [
              'Property',
              'Class Method',
              'Instance Method'
            ];
            html.push('<div class="type">',types[data.t-1],'</div>');
            html.push('<div class="signature">',data.i,'</div>');
          }
          break;
        case 25:  // CSS
          html.push('<div class="row"><span class="title">Default value:</span><div class="value">',data.d,'</div></div>');
          html.push('<div class="row"><span class="title">Expected values:</span>');
          var values = data.v;
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
          html.push('<div class="row"><span class="title">Inherited:</span><div class="value">',data.i,'</div></div>');
          break;
      }
    }

    if( this._active_function.navigate_immediately ) {
      this._elements.messages.html('Just a sec, once we\'ve loaded the details we\'ll send you off...').fadeIn('fast');
    } else if( this._active_function.loading ) {
      html.push('<div class="loading">Loading function details...</div>');
    }

    if( this._active_function.loading_social ) {
      html.push('<div class="social">Loading snaapits...</div>');
    } else if( this._active_function.social.length > 0 ) {
      html.push('<div class="social">');
      html.push('<div class="header">snaapits');
      if( !window.user_id ) {
        html.push(' - <a href="/login">log in</a> to submit links, comments, and code');
      }
      html.push('</div>');
      var social = this._active_function.social;
      for( var i = 0; i < social.length; ++i ) {
        html.push('<div class="row">');
        html.push('<div class="box"><div class="score">',social[i].score,'</div>');
        html.push('<div class="ratings"><span class="rater up">+</span><span class="rater down">-</span></div></div>');
        html.push('<div class="data">');
        if( social[i].type == 'link' ) {
          html.push('<div class="link"><a href="',social[i].data,'" class="external">',social[i].data,'</a></div>');
        } else if( social[i].type == 'snippet' ) {
          html.push('<div class="snippet"><pre>',social[i].data,'</pre></div>');
        }
        if( social[i].summary ) {
          html.push('<div class="summary">',social[i].summary,'</div>');
        }
        if( social[i].user_id ) {
          html.push('<div class="user" title="Wow, no usernames? Don\'t worry, they\'re coming!">Submitted by: User #',social[i].user_id,'</div>');
        }
        html.push('</div>');
        html.push('</div>');
      }
      html.push('</div>');
    } else if( this._active_function.social.length == 0 ) {
      html.push('<div class="social">');
      html.push('<div class="header">no snaapits found');
      if( !window.user_id ) {
        html.push(' - <a href="/login">log in</a> to submit links, comments, and code');
      }
      html.push('</div></div>');
    }

    if( undefined != window.user_id ) {
      html.push('<div class="socialness"><div class="methods"><ul>');
      html.push('<li>Add a link</li>');
      html.push('<li>Add a snippet</li>');
      html.push('</ul></div>');
      // Add a link
      html.push(
        '<div class="form" style="display:none"><form method="post" action="/function/addurl"><input type="hidden" name="category" value="',this._active_function.category,'" /><input type="hidden" name="id" value="',this._active_function.id,'" /><div class="row"><label for="url">URL:</label><div class="rightside"><input type="text" class="text" name="url" id="url" size="50" value="" /></div></div><div class="row"><label for="summary">Summary:</label><div class="rightside"><textarea name="summary" id="summary" cols="50" rows="5" ></textarea></div></div><div class="rightside"><input type="submit" class="button" value="add" /></div></form></div>');
      // Add a snippet
      html.push('<div class="form" style="display:none"><form method="post" action="/function/addsnippet"><input type="hidden" name="category" value="',this._active_function.category,'" /><input type="hidden" name="id" value="',this._active_function.id,'" /><label for="snippet">Snippet:</label><div class="rightside"><textarea class="code" name="snippet" id="snippet" cols="80" rows="10" wrap="off" ></textarea></div><div class="rightside"><input type="submit" class="button" value="add" /></div></form></div>');
      html.push('</div>');
    }

    if( !this._displaying_frame ) {
      var t = this;
      this._elements.whyjoin.fadeOut('fast', function() {
        t._elements.result
          .html(html.join(''))
          .fadeIn('fast');
      });
    } else {
      this._elements.result.html(html.join(''));
    }

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
          if( window.user_id ) {
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
              success : t._db.receive_vote_update.bind(t._db),
              failure : t._db.fail_to_receive_vote_update.bind(t._db)
            });
          }
        });
      });

      $(this._elementIDs.result + ' .down').each(function(index) {
        $(this).click(function() {
          if( window.user_id ) {
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
              success : t._db.receive_vote_update.bind(t._db),
              failure : t._db.fail_to_receive_vote_update.bind(t._db)
            });
          }
        });
      });
    }

    var t = this;
    $(this._elementIDs.result + ' a.external').click(function() {
      t._show_iframe($(this).attr('href'));
      return false;
    });
  },

  _show_iframe : function(url) {
    if( !this._displaying_frame || this._frame_url != url ) {
      var new_url = this._frame_url != url;

      var selection = this._list[this._selection];

      if( new_url ) {
        $.ajax({
          type    : 'POST',
          url     : '/function/viewframe',
          data    : {
            category  : selection.category,
            id        : selection.function_id
          }
        });
      }

      this._frame_url = url;
      var speed = 'fast';

      if( !this._displaying_frame ) {
        this._elements.goback.fadeIn(speed);
        this._elements.content_table.fadeOut(speed, function() {
          $('body').css({overflow:'hidden'});
          $('#footer').hide();

          this._elements.parent_table.css({position:'absolute'});
          if( new_url ) {
            this._elements.external.html('<div id="eww"><span class="reason">Just a sec, we\'re loading the reference page.<br/>thanks for using sna<span class="snaapi">api</span></span></div><iframe src="'+url+'" width="100%" height="100%" frameborder="0"></iframe>');
          }
          this._elements.external.fadeIn(speed);
        }.bind(this));  
      } else {  
        var src_iframe = this._elements.external.children('iframe');
        var t = this;
        src_iframe.fadeOut(speed, function() {
          $(this).after('<iframe src="'+url+'" style="display:none"></iframe>').remove();
          var dst_iframe = t._elements.external.children('iframe');
          dst_iframe.fadeIn('slow');
        });
      }

      this._displaying_frame = true;
    }
  },

  _hide_iframe : function(url) {
    if( this._displaying_frame ) {
      this._displaying_frame = false;
      var speed = 'fast';
      
      this._elements.goback.fadeOut(speed);
      this._elements.external.fadeOut(speed, function() {
        this._elements.content_table.fadeIn(speed);
        $('body').css({overflow:'visible'});

        this._elements.parent_table.css({position:'static'});
        $('#footer').show();
      }.bind(this));
    }
  },

  _receive_categories : function() {
    this._do_search();
  },

  _receive_function : function(category, id, succeeded) {
    if( this._active_function &&
        this._active_function.category == category &&
        this._active_function.id == id ) {
      this._render_function();
    }
  },

  _receive_hier : function() {
    if( window.sel && window.sel.hierarchy ) {
      this._db.ensure_hierarchy_loaded(window.sel.category, window.sel.hierarchy);
    }
  },

  _navigate_immediately : function(url) {
    this._elements.messages.fadeOut('fast', function() {
      $(this).html('');
    });
    this._show_iframe(url);
  },

  _receive_hierarchy : function() {
    this._render_function();
  },

  _gain_focus : function() {
    this._current_value = this._elements.input.val();
    this._do_search();

    this._elements.dropdown.fadeIn('fast');
    this._elements.input.select();
  },

  _lose_focus : function() {
    if( this._hover_timer ) {
      clearTimeout(this._hover_timer);
      this._hover_timer = null;
    }
    this._elements.dropdown.fadeOut('fast');
  }

};
