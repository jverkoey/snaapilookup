/**
 * snaapi filter bar
 *
 * @require-package core
 * @requires database.js
 */

Snap.FilterBar = function(elementIDs) {
  this._elementIDs = elementIDs;
  this._elements = {};
  for( var key in this._elementIDs ) {
    if( key != 'canvas' ) {
      this._elements[key] = $(this._elementIDs[key]);
    } else {
      this._elements[key] = $('#'+this._elementIDs[key]);
    }
  }

  this._elements.list = this._elements.filters.children('.list');
  this._elements.list_button = this._elements.filters.children('.list-button');
  this._elements.active = this._elements.filters.children('.active');

  // [type][category]
  this._active_filters = {};
  // [category]
  this._is_category_filtered = {};
  
  this._db = Snap.Database.singleton;
  this._db.register_callbacks({
    receive_categories    : this._receive_categories.bind(this)
  });
}

Snap.FilterBar.prototype = {

  toggle    : function(type, id, name) {
    if( this._is_category_filtered[id] ) {
      this._remove_filter(type, id);
    } else {
      if( undefined == this._active_filters[type] ) {
        this._active_filters[type] = {};
      }
      this._active_filters[type][id] = name;
    }

    this._save_active_filters();
    this._render_filters();
  },

  is_filtered : function(id) {
    return this._is_category_filtered[id];
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
      this._active_filters[window.sel.filter_type][window.sel.category] = this._db.id_to_category(window.sel.category);
    } else if( $.cookie('filters') ) {
      var filters = $.cookie('filters').split(',');
      for( var i = 0; i < filters.length; ++i ) {
        var type = this._db.id_to_type(filters[i]);
        if( undefined == this._active_filters[type] ) {
          this._active_filters[type] = {};
        }
        this._active_filters[type][filters[i]] = this._db.id_to_category(filters[i]);
      }
    }
    this._simplify_filters();
    this._render_filters();
  },

  _render_filters : function() {
    var html = [];
    var any_filters = false;
    var type_set = [];
    for( var filter_type in this._active_filters ) {
      var filter = this._active_filters[filter_type];
      var this_type = [];
      this_type.push('<div class="row">Filtering by <span class="type">',filter_type.toLowerCase());
      var filter_set = [];
      var count = 0;
      for( var filter_id in filter ) {
        var item = filter[filter_id];
        filter_set.push(
          '<span class="filter" id="'+
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
      type_set.push(this_type.join(''));
    }
    html.push(type_set.join('</div><div>'));
    if( any_filters ) {
      this._elements.active.html(html.join(''));

      var t = this;
      $(this._elementIDs.filters+' .filter').click(function() {
        var filter_type = this.id.substr(0, this.id.indexOf('-'));
        var filter_id = this.id.substr(this.id.indexOf('-') + 1);
        t._remove_filter(filter_type, filter_id);
      });
      this._elements.active.show();
    } else {
      this._elements.active.hide();
    }
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
  }

}
