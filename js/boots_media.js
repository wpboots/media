/**
 * Media - javascript
 *
 * @package Boots
 * @subpackage Media
 * @version 1.0.0
 * @license GPLv2
 *
 * Boots - The missing WordPress framework. http://wpboots.com
 *
 * Copyright (C) <2014>  <M. Kamal Khan>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */

(function($){
    "use strict";

    var BootsMediaObj = {

        init : function(ev, options, elem)
        {
            var self = this;
            self.elem = elem;
            self.$elem = $(elem);
            self.ev = $.fn.BootsMedia.ev;
            self.options = $.extend({}, $.fn.BootsMedia.options, options);

            // method calls
            self.image_upload();
        },

        image_upload : function()
        {
            var self = this;

            self.$elem.on(self.ev, function(event){

                event.preventDefault();

                var $button = $(this);

                if ($.fn.BootsMedia.file_frame)
                {
                    $.fn.BootsMedia.file_frame.open();
                    return;
                }

                $.fn.BootsMedia.file_frame = wp.media.frames.file_frame = wp.media({
                    title: $(this).data('title'),
                    button: {
                        text: $(this).data('button'),
                    },
                    multiple: self.options.multiple
                });

                $.fn.BootsMedia.file_frame.on('select', function(){
                    var attachment = self.options.multiple
                                   ? $.fn.BootsMedia.file_frame.state().get('selection').toJSON()
                                   : $.fn.BootsMedia.file_frame.state().get('selection').first().toJSON();

                    if(self.options.done)
                    {
                        self.options.done(attachment, $button);
                    }
                });

                $.fn.BootsMedia.file_frame.open();
            });
        }
    };

    $.fn.BootsMedia = function(ev, options) {
        return this.each(function(){
            var Obj = function(){
                function F(){};
                F.prototype = BootsMediaObj;
                return new F();
            }();
            Obj.init(ev, options, this);
        });
    };

    $.fn.BootsMedia.file_frame = null;

    $.fn.BootsMedia.ev = 'click';

    $.fn.BootsMedia.options = {
        multiple : false,
        done : function(attachment, $button){}
    };

})(jQuery);
