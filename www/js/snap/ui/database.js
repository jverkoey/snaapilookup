/**
 * snaapi database
 *
 * @require-package core
 */

Snap.Database = function() {
  if( Snap.Database.singleton ) {
    return Snap.Database.singleton;  // TODO: Does this work as expected?
  }

  // filters: #language or #framework
  // all: everything else
  this._database = {filters: {}, all: []};

  this._id_to_category = {};
  this._id_to_type = {};

  /**
   * [category][id] => hierarchy name
   */
  this._hierarchy_cache = null;
  
  this._function_cache = {};

  this._callbacks = {
    receive_categories    : [],
    receive_function      : [],
    receive_social        : [],
    receive_hier          : [],
    receive_hierarchy     : [],
    navigate_immediately  : []
  };

  Snap.Database.singleton = this;
};

Snap.Database.singleton = null;

Snap.Database.prototype = {

  register_callbacks : function(callbacks) {
    for( var key in callbacks ) {
      this._callbacks[key].push(callbacks[key]);
    }
  },

  load_functions : function(category, data) {
    for( var i = 0; i < data.length; ++i ) {
      data[i].l = data[i].n.toLowerCase();
      data[i].e = {
          type      : this._id_to_category[category],
          category  : category,
          hierarchy : data[i].h,
          function_id : data[i].i,
          name      : data[i].n,
          lower_name: data[i].l,
          matches   : null
      };
    }
    this._database.all[category] = data;
  },

  load : function() {
    $.ajax({
      type    : 'GET',
      url     : '/js/static/data.js?rev='+Revisions.static_js_build,
      dataType: 'json',
      success : this._receive_categories.bind(this),
      failure : this._fail_to_receive_categories.bind(this)
    });

    $.ajax({
      type    : 'GET',
      url     : '/js/static/hier.js?rev='+Revisions.static_hier_build,
      dataType: 'json',
      success : this._receive_hier.bind(this),
      failure : this._fail_to_receive_hier.bind(this)
    });

    for( var key in Revisions.static_fun_build ) {
      var revision = Revisions.static_fun_build[key];

      $.ajax({
        type    : 'GET',
        url     : '/js/static/fun/'+key+'.js?rev='+revision,
        dataType: 'script'
      });
    }
  },

  search : function(query, active_filters) {
    var results = [];
    var hash_results = {};

    if( query[0] == '#' ) {
      var query = $.trim(query.substr(1));
      var words = query.split(' ');
      var i = 0;
      while( i < words.length ) {
        if( words[i] == '' ) {
          words.splice(i, 1);
        } else {  
          words[i] = words[i].toLowerCase();
          ++i;
        }
      }

      // We're searching filters.
      if( this._database.filters.length > 0 ) {
        var filters = this._database.filters;
        for( var i = 0; i < filters.length; ++i ) {
          var filter = filters[i];
          for( var i2 = 0; i2 < filter.d.length; ++i2 ) {
            var unique_id = 'filter'+i+'-'+i2;
            var any_succeed = false;
            var any_fail = false;
            for( var i3 = 0; i3 < words.length || words.length == 0; ++i3 ) {
              var offset;
              var word;
              if( words.length == 0 ) {
                offset = 0;
                word = '';
              } else {
                word = words[i3];
                offset = filter.d[i2].n.toLowerCase().indexOf(word);
              }
              if( offset >= 0 ) {
                if( hash_results[unique_id] == undefined ) {
                  hash_results[unique_id] = {
                    type      : filter.t,
                    filter_id : filter.d[i2].i,
                    name      : filter.d[i2].n,
                    lower_name: filter.d[i2].n.toLowerCase(),
                    matches   : []
                  };
                }
                hash_results[unique_id].matches.push(word);

                any_succeed = true;
              } else {
                any_fail = true;
              }

              if( any_fail && any_succeed ) {
                delete hash_results[unique_id];
                break;
              }

              if( words.length == 0 ) {
                break;
              }
            }

            if( any_succeed && !any_fail ) {
              results.push(unique_id);
            }
          }
        }
      } else {
        return -1;
      }
    } else if( this._database.all.length > 0 ) {
      var words = query.split(' ');
      var i = 0;
      while( i < words.length ) {
        if( words[i] == '' ) {
          words.splice(i, 1);
        } else {  
          words[i] = words[i].toLowerCase();
          ++i;
        }
      }

      var any_filters = false;
      for( var key in active_filters ) {
        any_filters = true;
        break;
      }

      var all = this._database.all;
      for( var category in all ) {
        if( any_filters && undefined == active_filters[category] ) {
          continue;
        }

        var list = all[category];
        for( var i = 0; i < list.length; ++i ) {
          var unique_id = 'function'+category+'-'+i;

          var any_succeed = false;
          var any_fail = false;
          for( var i2 = 0; i2 < words.length; ++i2 ) {
            var offset = list[i].l.indexOf(words[i2]);
            if( offset >= 0 ) {
              if( hash_results[unique_id] == undefined ) {
                hash_results[unique_id] = list[i].e;
                hash_results[unique_id].matches = [];
              }

              hash_results[unique_id].matches.push(words[i2]);

              any_succeed = true;
            } else {
              any_fail = true;
            }

            if( any_fail && any_succeed ) {
              delete hash_results[unique_id];
              break;
            }
          }

          if( any_succeed && !any_fail ) {
            results.push(unique_id);
          }
        }
      }
    }

    // Calculate the score for each result.
    // Score = sum total of matched characters.
    for( var i = 0; i < results.length; ++i ) {
      var entry = hash_results[results[i]];

      var joined_areas = new Array(entry.name.length);
      for( var i2 = 0; i2 < entry.name.length; ++i2 ) {
        joined_areas[i2] = 0;
      }
      for( var i2 = 0; i2 < entry.matches.length; ++i2 ) {
        var word = entry.matches[i2];
        var offsets = entry.lower_name.gindexOf(word);

        for( var i3 = 0; i3 < offsets.length; ++i3 ) {
          var start = offsets[i3];
          joined_areas[start]++;

          var end = start + word.length;
          if( end < joined_areas.length ) {
            joined_areas[end]--;
          }
        }
      }

      entry.score = 0;
      var on = 0;
      var starts_with = joined_areas[0] > 0;
      for( var i2 = 0; i2 < joined_areas.length; ++i2 ) {
        on += joined_areas[i2];
        if( on > 0 ) {
          entry.score++;
          if( starts_with ) {
            entry.score++;    // Double up entries that start with the text.
          }
        } else {
          starts_with = false;
        }
      }

      entry.score /= entry.name.length;
    }

    // Sort by score.
    function by(left, right) {
      var left_entry = hash_results[left];
      var right_entry = hash_results[right];
      var result =
        (right_entry.score - left_entry.score) ||
        (left_entry.name.toLowerCase() < right_entry.name.toLowerCase() ? -1 : 0) ||
        (left_entry.name.toLowerCase() > right_entry.name.toLowerCase() ? 1 : 0) ||
        0;
      return result;
    }
    results = results.sort(by);

    var list;

    if( results.length > 0 ) {
      list = [];
      for( var i = 0; i < results.length; ++i ) {
        list.push(hash_results[results[i]]);
      }
    } else {
      list = null;
    }

    return list;
  },

  id_to_category : function(id) {
    return this._id_to_category[id];
  },

  id_to_type : function(id) {
    return this._id_to_type[id];
  },

  hierarchies_loaded : function() {
    return this._hierarchy_cache != null;
  },

  get_hierarchy : function(category, hierarchy) {
    return this._hierarchy_cache[category][hierarchy];
  },

  is_function_cached : function(category, id) {
    var result =
      undefined != this._function_cache[category] &&
      undefined != this._function_cache[category][id];
    return result;
  },

  get_function : function(category, id) {
    return this._function_cache[category][id];
  },

  request_function : function(category, id, name, type, hierarchy, silent) {
    if( undefined == this._function_cache[category] ) {
      this._function_cache[category] = {};
    }
    this._function_cache[category][id] = {
      name            : name,
      type            : type,
      category        : category,
      hierarchy       : hierarchy,
      id              : id,
      loading         : true,
      loading_social  : true
    };
    $.ajax({
      type    : 'GET',
      url     : '/function',
      dataType: 'json',
      data    : {
        category  : category,
        id        : id,
        silent    : silent
      },
      success : this._receive_function.bind(this),
      failure : this._fail_to_receive_function.bind(this)
    });

    $.ajax({
      type    : 'GET',
      url     : '/function/social',
      dataType: 'json',
      data    : {
        category  : category,
        id        : id
      },
      success : this._receive_social.bind(this),
      failure : this._fail_to_receive_social.bind(this)
    });
  },

  _notify_callbacks : function(name) {
    var args = [];
    for( var i = 1; i < arguments.length; ++i ) {
      args.push(arguments[i]);
    }
    for( var i = 0; i < this._callbacks[name].length; ++i ) {
      this._callbacks[name][i].apply(null, args);
    }
  },

  ensure_hierarchy_loaded : function(category, child) {
    if( this.hierarchies_loaded() ) {
      var hierarchy = this._hierarchy_cache[category][child];
      var to_request = [];
      if( undefined == hierarchy.source_url ) {
        to_request.push(child);
      }
      if( hierarchy.ancestors ) {
        for( var i = 0; i < hierarchy.ancestors.length; ++i ) {
          if( undefined == this._hierarchy_cache[category][hierarchy.ancestors[i]].source_url ) {
            to_request.push(hierarchy.ancestors[i]);
          }
        }
      }
 
      if( to_request.length ) {
        $.ajax({
          type : 'GET',
          url : '/hierarchy/info',
          dataType: 'json',
          data : {
            c : category,
            h : to_request.join(',')
          },
          success : this._receive_hierarchy.bind(this),
          failure : this._fail_to_receive_hierarchy.bind(this)
        });
      }
    }
  },

  _receive_hierarchy : function(result, textStatus) {
    if( result.s ) {
      for( var category in result.i ) {
        var hierarchies = result.i[category];
        for( var hierarchy in hierarchies ) {
          this._hierarchy_cache[category][hierarchy].source_url = hierarchies[hierarchy].source_url;
        }
      }
      this._notify_callbacks('receive_hierarchy');
    }
  },
 
  _fail_to_receive_hierarchy : function(result, textStatus) {
  },

  _receive_categories : function(result, textStatus) {
    this._database.filters = result;

    // Compile the data into an id=>category map for quick access.
    for( var i = 0; i < result.length; ++i ) {
      var data = result[i].d;
      for( var i2 = 0; i2 < data.length; ++i2 ) {
        var item = data[i2];
        this._id_to_category[item.i] = item.n;
        this._id_to_type[item.i] = result[i].t;
      }
    }

    this._notify_callbacks('receive_categories');
  },

  _fail_to_receive_categories : function(result, textStatus) {
  },

  _receive_hier : function(result, textStatus) {
    this._hierarchy_cache = {};
    for( var category in result ) {
      if( undefined == this._hierarchy_cache[category] ) {
        this._hierarchy_cache[category] = {};
      }

      function process_children(hierarchy) {
        for( var i = 0; i < hierarchy.length; ++i ) {
          var item = hierarchy[i];
          if( undefined == this._hierarchy_cache[category][item.d.i] ) {
            this._hierarchy_cache[category][item.d.i] = {};
          }
          this._hierarchy_cache[category][item.d.i].name = item.d.n;
          this._hierarchy_cache[category][item.d.i].ancestors = item.d.h;
          if( undefined != item.c ) {
            process_children.bind(this)(item.c);
          }
        }
      }
      process_children.bind(this)(result[category]);
    }  
    this._notify_callbacks('receive_hier');
  },

  _fail_to_receive_hier : function(result, textStatus) {
  },

  _receive_social : function(result, textStatus) {
    if( result.succeeded ) {
      var category = result.category;
      var id = result.id;

      var function_info = this._function_cache[category][id];

      function_info.social = result.data;
      function_info.loading_social = false;
      
      this._notify_callbacks('receive_social', result.category, result.id);
    }
  },

  _fail_to_receive_social : function(result, textStatus) {
  },

  _receive_function : function(result, textStatus) {
    if( result.succeeded ) {
      var category = result.category;
      var id = result.id;

      var function_info = this._function_cache[category][id];

      for( var key in result.data ) {
        function_info[key] = result.data[key];
      }
      function_info.loading = false;

      if( function_info.data ) {
        switch( function_info.category ) {
          case 30:  // Firebug
          case 9:   // PHP
          case 28:  // django
          case 26:  // Zend
            function_info.data = function_info.data.replace(/<\/s>/g, '</span>');
            function_info.data = function_info.data.replace(/<st>/g, '<span class="type">');
            function_info.data = function_info.data.replace(/<si>/g, '<span class="initializer">');
            function_info.data = function_info.data.replace(/<sm>/g, '<span class="methodname">');
            function_info.data = function_info.data.replace(/<smp>/g, '<span class="methodparam">');
            function_info.data = function_info.data.replace(/<sp>/g, '<span class="methodarg">');
            break;
          case 29:  // iPhone
            function_info.data = window["eval"]("(" + function_info.data + ")");
            break;
          case 25:  // CSS
            function_info.data = window["eval"]("(" + function_info.data + ")");
            if( !function_info.data.d ) {
              function_info.data.d = 'Not defined';
            }
            if( !function_info.data.i ) {
              function_info.data.i = 'No';
            }
            break;
        }
      }

      if( function_info.navigate_immediately && result.data.url ) {
        delete function_info.navigate_immediately;
        this._notify_callbacks('navigate_immediately', result.data.url);
      }

      this._notify_callbacks('receive_function', result.category, result.id);
    }
  },

  _fail_to_receive_function : function(result, textStatus) {
  },

  receive_vote_update : function(result, textStatus) {
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

  fail_to_receive_vote_update : function(result, textStatus) {
  }

};
