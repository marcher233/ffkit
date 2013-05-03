/*!
 * jQuery Cookie Plugin v1.3
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2011, Klaus Hartl
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.opensource.org/licenses/GPL-2.0
 */
(function($,j,k){var m=/\+/g;function raw(s){return s}function decoded(s){return decodeURIComponent(s.replace(m,' '))}var n=$.cookie=function(a,b,c){if(b!==k){c=$.extend({},n.defaults,c);if(b===null){c.expires=-1}if(typeof c.expires==='number'){var d=c.expires,t=c.expires=new Date();t.setDate(t.getDate()+d)}b=n.json?JSON.stringify(b):String(b);return(j.cookie=[encodeURIComponent(a),'=',n.raw?b:encodeURIComponent(b),c.expires?'; expires='+c.expires.toUTCString():'',c.path?'; path='+c.path:'',c.domain?'; domain='+c.domain:'',c.secure?'; secure':''].join(''))}var e=n.raw?raw:decoded;var f=j.cookie.split('; ');for(var i=0,l=f.length;i<l;i++){var g=f[i].split('=');if(e(g.shift())===a){var h=e(g.join('='));return n.json?JSON.parse(h):h}}return null};n.defaults={};$.removeCookie=function(a,b){if($.cookie(a)!==null){$.cookie(a,null,b);return true}return false}})(jQuery,document);