(function($){var h,shadow,trigger,content,hash,currentTarget;var j={menuStyle:{listStyle:'none',padding:'1px',margin:'0px',backgroundColor:'#fff',border:'1px solid #999',width:'100px'},itemStyle:{margin:'0px',color:'#000',display:'block',cursor:'default',padding:'3px',border:'1px solid #fff',backgroundColor:'transparent'},itemHoverStyle:{border:'1px solid #0a246a',backgroundColor:'#b6bdd2'},eventPosX:'pageX',eventPosY:'pageY',shadow:true,onContextMenu:null,onShowMenu:null};$.fn.contextMenu=function(b,c){if(!h){h=$('<div id="jqContextMenu"></div>').hide().css({position:'absolute',zIndex:'500'}).appendTo('body').bind('click',function(e){e.stopPropagation()})}if(!shadow){shadow=$('<div></div>').css({backgroundColor:'#000',position:'absolute',opacity:0.2,zIndex:499}).appendTo('body').hide()}hash=hash||[];hash.push({id:b,screen_name:screen_name,status:status,menuStyle:$.extend({},j.menuStyle,c.menuStyle||{}),itemStyle:$.extend({},j.itemStyle,c.itemStyle||{}),itemHoverStyle:$.extend({},j.itemHoverStyle,c.itemHoverStyle||{}),bindings:c.bindings||{},shadow:c.shadow||c.shadow===false?c.shadow:j.shadow,onContextMenu:c.onContextMenu||j.onContextMenu,onShowMenu:c.onShowMenu||j.onShowMenu,eventPosX:c.eventPosX||j.eventPosX,eventPosY:c.eventPosY||j.eventPosY});var d=hash.length-1;$(this).bind('contextmenu',function(e){var a=(!!hash[d].onContextMenu)?hash[d].onContextMenu(e):true;if(a)display(d,this,e,c);return false});return this};function display(c,d,e,f){var g=hash[c];content=$('#'+g.id).find('ul:first').clone(true);content.css(g.menuStyle).find('li').css(g.itemStyle).hover(function(){$(this).css(g.itemHoverStyle)},function(){$(this).css(g.itemStyle)}).find('img').css({verticalAlign:'middle',paddingRight:'2px'});h.html(content);if(!!g.onShowMenu)h=g.onShowMenu(e,h);$.each(g.bindings,function(a,b){$('#'+a,h).bind('click',function(e){hide();b(d,currentTarget)})});h.css({'left':e[g.eventPosX],'top':e[g.eventPosY]}).show();if(g.shadow)shadow.css({width:h.width(),height:h.height(),left:e.pageX+2,top:e.pageY+2}).show();$(document).one('click',hide)}function hide(){h.hide();shadow.hide()}$.contextMenu={defaults:function(b){$.each(b,function(i,a){if(typeof a=='object'&&j[i]){$.extend(j[i],a)}else j[i]=a})}}})(jQuery);$(function(){$('div.contextMenu').hide()});