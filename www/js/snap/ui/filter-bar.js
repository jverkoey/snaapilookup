/**
 * snaapi filter bar
 *
 * @require-package core
 * @requires database.js
 */

Snap.FilterBar = function(elementIDs) {
  this._elementIDs = elementIDs;
  this._elementIDs.list = this._elementIDs.filters+' .list';
  this._elementIDs.list_button = this._elementIDs.filters+' .list-button';
  this._elementIDs.active = this._elementIDs.filters+' .active';
  this._elements = {};
  for( var key in this._elementIDs ) {
    if( key != 'canvas' ) {
      this._elements[key] = $(this._elementIDs[key]);
    } else {
      this._elements[key] = $('#'+this._elementIDs[key]);
    }
  }
/*
  37 	language 	Clojure
  	Edit 	Delete 	35 	framework 	android
  	Edit 	Delete 	9 	language 	PHP
  	Edit 	Delete 	34 	framework 	twitter
  	Edit 	Delete 	33 	language 	Python 2.6.1
  	Edit 	Delete 	32 	framework 	jQuery
  	Edit 	Delete 	31 	language 	Javascript
  	Edit 	Delete 	30 	framework 	Firebug
  	Edit 	Delete 	29 	framework 	iPhone
  	Edit 	Delete 	28 	framework 	django
  	Edit 	Delete 	36 	framework 	mootools
  	Edit 	Delete 	27 	framework 	Facebook
  	Edit 	Delete 	26 	framework 	Zend
  	Edit 	Delete 	25 	language 	CSS
  	Edit 	Delete 	24 	language 	Python 3.0.1*/

  this._packages = [
    {name: 'Web (jQuery)', filters: [31, 25, 32, 30]},
    {name: 'Web (mootools)', filters: [31, 25, 36, 30]},
    {name: 'Web (Zend)', filters: [9, 26]},
    {name: 'Web (Django)', filters: [33, 28]}
  ];

  this._filter_list_shown = false;

  // [type][category]
  this._active_filters = {};
  // [category]
  this._is_category_filtered = {};

  this._elements.list_button.click(this._toggle_filter_list.bind(this));

  this._callbacks = [];

  this._db = Snap.Database.singleton;
  this._db.register_callbacks({
    receive_categories    : this._receive_categories.bind(this)
  });
}

Snap.FilterBar.prototype = {

  toggle    : function(type, id) {
    if( this._is_category_filtered[id] ) {
      this._remove_filter(type, id);
    } else {
      if( undefined == this._active_filters[type] ) {
        this._active_filters[type] = {};
      }
      this._active_filters[type][id] = this._db.id_to_name(id);
    }

    this.refresh();
  },

  clearAll    : function() {
    this._active_filters = {};
  },

  enable      : function(type, id) {
    if( undefined == this._active_filters[type] ) {
      this._active_filters[type] = {};
    }
    this._active_filters[type][id] = this._db.id_to_name(id);
  },

  refresh     : function() {
    this._save_active_filters();
    this._render_filters();
    this._notify_callbacks();
  },

  is_filtered : function(id) {
    return this._is_category_filtered[id];
  },

  any_filtered : function() {
    for( var key in this._is_category_filtered ) {
      return true;
    }
    return false;
  },

  register_change_callback : function(fn) {
    this._callbacks.push(fn);
  },

  _notify_callbacks : function() {
    for( var i = 0; i < this._callbacks.length; ++i ) {
      this._callbacks[i]();
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

  _simplify_filters : function() {
    this._is_category_filtered = {};
    for( var filter_type in this._active_filters ) {
      var filter = this._active_filters[filter_type];
      for( var filter_id in filter ) {
        this._is_category_filtered[filter_id] = true;
      }
    }
  },

  _save_active_filters : function() {
    this._simplify_filters();
    $.cookie('filters', this._flatten_filters());
  },

  _receive_categories : function() {
    if( window.sel ) {
      if( undefined == this._active_filters[window.sel.filter_type] ) {
        this._active_filters[window.sel.filter_type] = {};
      }
      this._active_filters[window.sel.filter_type][window.sel.category] = this._db.id_to_name(window.sel.category);
    } else if( $.cookie('filters') ) {
      var filters = $.cookie('filters').split(',');
      for( var i = 0; i < filters.length; ++i ) {
        var type = this._db.id_to_type(filters[i]);
        if( undefined == this._active_filters[type] ) {
          this._active_filters[type] = {};
        }
        this._active_filters[type][filters[i]] = this._db.id_to_name(filters[i]);
      }
    }
    this._simplify_filters();
    this._render_filters();
  },

  _toggle_filter_list : function() {
    if( this._filter_list_shown ) {
      this._elements.list.slideUp('fast');
      this._elements.list_button.html('View all currently supported frameworks and languages');
    } else {
      this._elements.list.slideDown('fast');
      this._elements.list_button.html('Hide');
    }

    this._filter_list_shown = !this._filter_list_shown;
  },

  _render_filter_list : function() {
    var html = [];
    html.push('<div class="header">All frameworks and languages</div><table><tbody><tr>');
    var filters = this._db.get_filters();
    for( var i = 0; i < filters.length; ++i ) {
      var filter_type = filters[i].t;
      html.push('<td class="col',i,'"><div class="cat_header">',filter_type,'</div>');
      var filter_list = filters[i].d;
      for( var i2 = 0; i2 < filter_list.length; ++i2 ) {
        var filter_id = filter_list[i2].i;
        var toggle_class = this._is_category_filtered[filter_id] ? 'hide' : 'show';
        html.push('<div class="filter ',toggle_class,'" title="Click to toggle" id="toggle_',
          filter_type,'-',filter_id,'">',filter_list[i2].n,'</div>');
      }
      html.push('</td>');
    }
    html.push('</tr></tbody></table>');
    html.push('<div class="packages"><div class="header">Packages</div>');

    for( var i = 0; i < this._packages.length; ++i ) {
      var package = this._packages[i];
      var toggle_class = 'show';
      html.push('<div class="filter ',toggle_class,'" title="Click to use this package" id="package_',
        i,'">',package.name,'</div>');
    }

    html.push('</div><div class="clearfix"></div><div class="all-selected"></div>');
    this._elements.list.html(html.join(''));

    var t = this;
    $(this._elementIDs.list+' .filter').click(function() {
      var toggle_prefix = 'toggle_';
      var package_prefix = 'package_';
      if( this.id.indexOf(toggle_prefix) >= 0 ) {
        var filter_type = this.id.substr(toggle_prefix.length, this.id.indexOf('-') - toggle_prefix.length);
        var filter_id = this.id.substr(this.id.indexOf('-') + 1);
        t.toggle(filter_type, filter_id);
      } else if( this.id.indexOf(package_prefix) >= 0 ){
        var package = t._packages[this.id.substr(package_prefix.length)];
        t.clearAll();
        for( var i = 0; i < package.filters.length; ++i ) {
          var filter_id = package.filters[i];
          t.enable(t._db._id_to_type[filter_id], filter_id);
        }
        t.refresh();
      }
    });
  },

  _render_filters : function() {
    var html = [];
    var any_filters = false;
    var type_set = [];
    for( var filter_type in this._active_filters ) {
      var filter = this._active_filters[filter_type];
      var this_type = [];
      this_type.push('<div class="row">');
      if( any_filters ) {
        this_type.push('and');
      } else {
        this_type.push('Filtering by');
      }
      this_type.push(' <span class="type">',filter_type.toLowerCase());
      var filter_set = [];
      var count = 0;
      for( var filter_id in filter ) {
        var item = filter[filter_id];
        filter_set.push(
          '<span class="filter" id="remove_'+
          filter_type+'-'+filter_id+
          '" title="Click to remove">'+item+'<span class="xme">X</span></span>');
        any_filters = true;
        count++;
      }
      if( count > 1 ) {
        this_type.push('s');
      }
      this_type.push(': </span>');
      if( filter_set.length > 2 ) {
        var last = filter_set.splice(filter_set.length - 1);
        this_type.push(filter_set.join(', '));
        this_type.push(' and ',last);
      } else if( filter_set.length == 2 ) {  
        this_type.push(filter_set.join(' and '));
      } else {
        this_type.push(filter_set[0]);
      }
      this_type.push('</div>');
      type_set.push(this_type.join(''));
    }
    html.push(type_set.join(''));
    if( any_filters ) {
      this._elements.active.html(html.join(''));

      var t = this;
      $(this._elementIDs.active+' .filter').click(function() {
        var filter_type = this.id.substr('remove_'.length, this.id.indexOf('-') - 'remove_'.length);
        var filter_id = this.id.substr(this.id.indexOf('-') + 1);
        t._remove_filter(filter_type, filter_id);
      });
      this._elements.active.show();
    } else {
      this._elements.active.hide();
    }
    this._render_filter_list();
  },

  _remove_filter : function(filter_type, filter_id) {
    delete this._active_filters[filter_type][filter_id];
    var any_filters_left = false;
    for( var key in this._active_filters[filter_type] ) {
      any_filters_left = true;
      break;
    }
    if( !any_filters_left ) {
      delete this._active_filters[filter_type];
    }

    this._save_active_filters();
    this._render_filters();
    this._notify_callbacks();
  }

}
