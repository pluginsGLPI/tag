/*!
 * Color Components For Ext JS Library 3.xx
 * Copyright(c) 2012 ext-press.
 * colortools@ext-press.com
 * LGPL license http://www.gnu.org/copyleft/lesser.html
 * Yavuz Taner Küçükarslan / mjjolnir
 */

Ext.namespace('Ext.ux', 'Ext.ux.color','Ext.ux.color.menu');

/* Usefull Utils */ 
/**
* Hexedecimal vType
*/
Ext.applyIf(Ext.form.VTypes, {
            hexcolor:  function(v) {
                return /#*[0-9a-fA-F]{2,6}$/.test(v);
            },
            hexcolorText: 'Must be a hexedecimal color value',
            hexcolorMask: /[0-9a-fA-F]/i
        });

/**
* String Utils
*/
Ext.applyIf(String.prototype, {
    ucFirst : function (allWords) {
        if (allWords) {
            var x = this.split(/\s+/g);
            for (var i = 0; i < x.length; i++) {
                var parts = x[i].match(/(\w)(\w*)/);
                x[i] = parts[1].toUpperCase() + parts[2].toLowerCase();
            }
            return x.join(' ');
        } else {
            var f = this.charAt(0).toUpperCase();
            return f + this.substr(1);
        }
    }
});
/**
* Array Utils
*/
Ext.applyIf(Array.prototype, {
    insertAt : function(o, index){    
        if ( index > -1 && index <= this.length ) {
            this.splice(index, 0, o);
            return true;
        }        
        return false;
    },
    contains: function(element,insensitive) {
        for (var i = 0; i < this.length; i++){
            if (insensitive == true) {
                if (this[i].toLowerCase() == element.toLowerCase()){
                      return true;
                }
            } else {
                if (this[i] == element){
                      return true;
                }
            }
        }
        return false;
    }
});

/*eo utils */



/**
 * @class Ext.ux.color.color
 * Provides color conversation and automatic color validation. The value of the specified color is used to convert other formats, such as rgb,hsv,hex,
 * For Example:
 * <pre><code>
        var color = new Ext.ux.color.color("#FFFFFF");
        var rgb = color.getRgb();
    </code></pre>
 * @constructor
 * Create a new color
 * @param {string} color value. Possible values: rgb(255,255,255) , (object){r:255,g:255,b:255},(object){h:0,s:0,v:100}, #FFF (short usage), #FFFFFF, FFFFFF
 */
Ext.ux.color.color = function(value) {
    var color = {
		r: 0,
		g: 0,
		b: 0,
		
		h: 0,
		s: 0,
		v: 0,
		
		hex: '',
        
		/**
         * Changes the color value for red,green,blue params.
         * @param {Mixed} integer red color value ,object {r:255,g:255,b:255} or array [255,255,255]
         * @param {Integer} green color value.(Max=255, Min=0)
         * @param {Integer} blue color value.(Max=255,Min=0)
         */
		setRgb: function(r, g, b) {
		  if(Ext.isArray(r)) { return this.setRgb.call( this, r[0], r[1], r[2] ); }
          if(Ext.isObject(r)) { return this.setHsv.call( this, r.r, r.g, r.b ); }
          
           if (/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/i.test(r)){ // test rgb (0,0,0) format
                var m = r.match(/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/i);
                return this.setRgb.call( this, m[1], m[2], m[3] );
            }
		  if (Ext.isDefined(r) && Ext.isDefined(g) && Ext.isDefined(b)) {
		        this.r = r;
    			this.g = g;
    			this.b = b;
    						
    			var newHsv = this.utils.rgbToHsv(this);
    			this.h = newHsv.h;
    			this.s = newHsv.s;
    			this.v = newHsv.v;
    			
    			this.hex = this.utils.validateHex(this.utils.rgbToHex(this));
		  }					
		},
		
        /**
         * Changes the color value for hue,saturation,value(brightness) params.
         * @param {Mixed} integer hue value ,object {h:100,s:0,v:100} or array [100,0,100]
         * @param {Integer} saturation value.(Max=100, Min=0)
         * @param {Integer} blue color value.(Max=100,Min=0)
         */
		setHsv: function(h, s, v) {
		  if(Ext.isArray(h)) { return this.setHsv.call( this, h[0], h[1], h[2] ); }
          if(Ext.isObject(h)) { return this.setHsv.call( this, h.h, h.s, h.v ); }
		  if (Ext.isDefined(h) && Ext.isDefined(s) && Ext.isDefined(v)) {
                this.h = h;
    			this.s = s;
    			this.v = v;
    			var newRgb = this.utils.hsvToRgb(this);
    			this.r = newRgb.r;
    			this.g = newRgb.g;
    			this.b = newRgb.b;	
    			
    			this.hex = this.utils.validateHex(this.utils.rgbToHex(newRgb));	
		  }
		},
		
        /**
         * Changes the color value for hexedecimal (with or without # symbol) params.
         * @param {String} string hex value , usage : #FFFFFF or FFFFFF
         */
		setHex: function(hex) {
		  if (/#*[0-9a-fA-F]{2,6}$/im.test(hex)) {
                this.hex = hex.replace("#","");
			
    			var newRgb = this.utils.hexToRgb(this.hex);
    			this.r = newRgb.r;
    			this.g = newRgb.g;
    			this.b = newRgb.b;
    			
    			var newHsv = this.utils.rgbToHsv(newRgb);
    			this.h = newHsv.h;
    			this.s = newHsv.s;
    			this.v = newHsv.v;
		  }			
		},
        /**
         * For HTML color styles.
         * @return {String} string hex value with # symbol.
         */
        getHexColor:function() {
          return Ext.isEmpty(this.hex) ? undefined :  "#"+this.utils.validateHex(this.hex);  
        },
        
        /**
         * @return {String} string hex value without # symbol.
         */
        getHex:function() {
          return this.hex;  
        },
        
        /**
         * @return {Object} rgb javascript object. 
         */
        getRgb:function() {
            return {r:this.r,g:this.g,b:this.b};
        },
        
        /**
         * @return {Object} hsv javascript object. 
         */
        getHsv:function() {
            return {h:this.h,s:this.s,v:this.v};
        },
        
        /**
         * color conversion funtions 
         */
        utils:{
            	hexToRgb: function(hex) {
            		hex = this.validateHex(hex);
            
            		var r='00', g='00', b='00';
                    if (hex.length == 3) {
                        r = hex.substring(0,1) + hex.substring(0,1);
            			g = hex.substring(1,2) + hex.substring(1,2);
            			b = hex.substring(2,3) + hex.substring(2,3);	
                    } else if (hex.length == 6) {
            			r = hex.substring(0,2);
            			g = hex.substring(2,4);
            			b = hex.substring(4,6);	
            		} else {
            			if (hex.length > 4) {
            				r = hex.substring(4, hex.length);
            				hex = hex.substring(0,4);
            			}
            			if (hex.length > 2) {
            				g = hex.substring(2,hex.length);
            				hex = hex.substring(0,2);
            			}
            			if (hex.length > 0) {
            				b = hex.substring(0,hex.length);
            			}					
            		}
            		return { r:this.hexToInt(r), g:this.hexToInt(g), b:this.hexToInt(b) };
            	},
            	validateHex: function(hex) {
            		hex = new String(hex).toUpperCase();
            		hex = hex.replace(/[^A-F0-9]/g, '0');
            		if (hex.length > 6) hex = hex.substring(0, 6);
            		return hex;
            	},
            	webSafeDec: function (dec) {
            		dec = Math.round(dec / 51);
            		dec *= 51;
            		return dec;
            	},
            	hexToWebSafe: function (hex) {
            		var r, g, b;
            
            		if (hex.length == 3) {
            			r = hex.substring(0,1);
            			g = hex.substring(1,2);
            			b = hex.substring(2,3);
            		} else {
            			r = hex.substring(0,2);
            			g = hex.substring(2,4);
            			b = hex.substring(4,6);
            		}
            		return intToHex(this.webSafeDec(this.hexToInt(r))) + this.intToHex(this.webSafeDec(this.hexToInt(g))) + this.intToHex(this.webSafeDec(this.hexToInt(b)));
            	},
            	rgbToWebSafe: function(rgb) {
            		return {r: this.webSafeDec(rgb.r), g: this.webSafeDec(rgb.g), b: this.webSafeDec(rgb.b) };
            	},
            	rgbToHex: function (rgb) {
            		return this.intToHex(rgb.r) + this.intToHex(rgb.g) + this.intToHex(rgb.b);
            	},
            	intToHex: function (dec){
            		var result = (parseInt(dec).toString(16));
            		if (result.length == 1)
            			result = ("0" + result);
            		return result.toUpperCase();
            	},
            	hexToInt: function (hex){
            		return(parseInt(hex,16));
            	},
            	rgbToHsv: function (rgb) {
            
            		var r = rgb.r / 255;
            		var g = rgb.g / 255;
            		var b = rgb.b / 255;
            
            		hsv = {h:0, s:0, v:0};
            
            		var min = 0
            		var max = 0;
            
            		if (r >= g && r >= b) {
            			max = r;
            			min = (g > b) ? b : g;
            		} else if (g >= b && g >= r) {
            			max = g;
            			min = (r > b) ? b : r;
            		} else {
            			max = b;
            			min = (g > r) ? r : g;
            		}
            
            		hsv.v = max;
            		hsv.s = (max) ? ((max - min) / max) : 0;
            
            		if (!hsv.s) {
            			hsv.h = 0;
            		} else {
            			delta = max - min;
            			if (r == max) {
            				hsv.h = (g - b) / delta;
            			} else if (g == max) {
            				hsv.h = 2 + (b - r) / delta;
            			} else {
            				hsv.h = 4 + (r - g) / delta;
            			}
            
            			hsv.h = parseInt(hsv.h * 60);
            			if (hsv.h < 0) {
            				hsv.h += 360;
            			}
            		}
            		
            		hsv.s = parseInt(hsv.s * 100);
            		hsv.v = parseInt(hsv.v * 100);
            
            		return hsv;
            	},
            	hsvToRgb: function (hsv) {
            
            		var h = hsv.h / 360; var s = hsv.s / 100; var v = hsv.v / 100;
                    var RGB = {r:0,g:0,b:0};
                
                    if (s == 0) {
                        RGB = this.validateRGB({r:parseInt((v * 255)), g:parseInt((v * 255)), b:parseInt((v * 255))});
                    } else {
                        var_h = h * 6;
                        var_i = Math.floor(var_h);
                        var_1 = v * (1 - s);
                        var_2 = v * (1 - s * (var_h - var_i));
                        var_3 = v * (1 - s * (1 - (var_h - var_i)));
                        
                        if (var_i == 0) {var_r = v; var_g = var_3; var_b = var_1}
                        else if (var_i == 1) {var_r = var_2; var_g = v; var_b = var_1}
                        else if (var_i == 2) {var_r = var_1; var_g = v; var_b = var_3}
                        else if (var_i == 3) {var_r = var_1; var_g = var_2; var_b = v}
                        else if (var_i == 4) {var_r = var_3; var_g = var_1; var_b = v}
                        else {var_r = v; var_g = var_1; var_b = var_2};
                        
                        RGB = this.validateRGB({r:parseInt((var_r * 255)), g:parseInt((var_g * 255)), b:parseInt((var_b * 255))});
                    }
            	    return RGB;
                },
                validateRGB:function(rgb) {
                    rgb.r = rgb.r < 0   ? 0   :rgb.r;
                    rgb.r = rgb.r > 255 ? 255 :rgb.r;
                    rgb.g = rgb.g < 0   ? 0   :rgb.g;
                    rgb.g = rgb.g > 255 ? 255 :rgb.g;
                    rgb.b = rgb.b < 0   ? 0   :rgb.b;
                    rgb.b = rgb.b > 255 ? 255 :rgb.b;
                    return rgb;
                }
          } 
	};
    if (value) {
		if (/#*[0-9a-fA-F]{2,6}$/im.test(value))
			color.setHex(value);
		else if (Ext.isDefined(value.r))
			color.setRgb(value.r, value.g, value.b);
        else if (/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/i.test(value)){ // test rgb (?,?,?) format
            var m = value.match(/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/i);
            color.setRgb(m[1], m[2], m[3]);
        }
		else if (Ext.isDefined(value.h))
			color.setHsv(value.h, value.s, value.v);			
	}

   return color;
};





/**
 * @class Ext.ux.color.Slider
 * @extends Ext.BoxComponent
 * Slider which supports area, vertical or horizontal orientation, axis clicking and animation. Can be added as an item to any container. Example usage:
<pre>
new Ext.ux.color.Slider({
    renderTo: Ext.getBody(),
    width: 200,
    sliderMode:"area",
    xMinValue: 0,
    xMaxValue: 100,
    yMinValue:0,
    yMaxValue:100
});
</pre>
 */
Ext.ux.color.Slider = Ext.extend(Ext.BoxComponent,{
    //private
    itemCls:"x-cpm-slider",
    //private
    layerCls:"x-cpm-layer",
    //private
    bodyCls:"x-cpm-slider-body",
    /**
     * @cfg {Boolean} Turn on or off qtips. Defaults to false
     */
    enableTip:false,
    /**
     * @cfg {String} Show quick tip for slider thumb
     */
    qTip:"",
    //private
    thumbCls:"x-cpm-slider-picker",
    /**
     * @cfg {String} Configure slider mode. Possible values: "area","horizontal","vertical". Defaults to "area" 
     */
    sliderMode:"area",
    /**
     * @cfg {Number} xMinValue The minimum value for the slider x-axis. Defaults to 0.
     */
    xMinValue:0,
    /**
     * @cfg {Number} xMaxValue The maximum value for the slider x-axis. Defaults to 100.
     */
    xMaxValue:100,
    /**
     * @cfg {Number} yMinValue The minimum value for the slider y-axis. Defaults to 0.
     */
    yMinValue:0,
    /**
     * @cfg {Number} yMaxValue The maximum value for the slider y-axis. Defaults to 100.
     */
    yMaxValue:100,
    xyPosition:[0,0],
    layerCount:4,
    layers:null,
    constructor:function(config) {
        config = config || {};
        Ext.apply(this,config);
        this.listeners = config.listeners;
        this.width = config.width || 100;
        this.height = config.height || 100;
        
         this.addEvents({
            "change" : true,
            "select" : true
        });
        
        Ext.ux.color.Slider.superclass.constructor.call(this, config);

    },

    onRender:function(ct,position) {
        this.autoEl = {
            tag: 'div',
            cls: this.itemCls
        };
        
        Ext.ux.color.Slider.superclass.onRender.call(this,ct,position);
        
            var dh = Ext.DomHelper;
            
            var bodyConfig = {
                tag:"div",
                cls:this.bodyCls
            }
            this.body = dh.append(this.el.dom,bodyConfig,true);
            this.layers= [];
            for (var i = 0; i < this.layerCount; i++) {
                var layerConfig = {
                    id:Ext.id(),
                    tag:"div",
                    cls:this.layerCls
                }
                this.layers.push(dh.append(this.body ,layerConfig,true));
            }
            
            var thumbConfig = {
                tag:"div",
                cls:this.thumbCls,
                qTip:this.enableTip ?  this.qTip || "" : ""
            }
            this.thumb = dh.append(this.el.dom,thumbConfig,true);
            this.thumbDD = new Ext.dd.DD(this.thumb.dom, 'thumbDDGroup');
            this.thumbDD.onDrag = this.sliderDDHandler.createDelegate( this );
            
            this.thumb.on("dblclick",function(e){
                this.fireEvent("select",this,this.xyPosition);
            },this);
            this.thumb.on("click",function(e){
                if (e.ctrlKey) {
                    this.fireEvent("select",this,this.xyPosition);
                }
            },this);    
                        
            this.el.on("mousedown",function(e){
                this.oldPos = this.xyPosition;
                var point = this.setMousePosition(e);
                this.setThumbPosition(point,true);
            },this);
            
            this.on("resize",this.onResizeHandler,this);
    },
    onResizeHandler:function(o,w,h) {
        var paddings = {
                t:this.el.getPadding("t") + this.body.getBorderWidth("t") + this.body.getMargins("t"),
                l:this.el.getPadding("l") + this.body.getBorderWidth("l") + this.body.getMargins("l"),
                r:this.el.getPadding("r") + this.body.getBorderWidth("r") + this.body.getMargins("r"),
                b:this.el.getPadding("b") + this.body.getBorderWidth("b") + this.body.getMargins("b")
            }
        
        this.body.setSize(w-(paddings.l+paddings.r),h-(paddings.t+paddings.b));
        
        for (var i = 0; i < this.layers.length; i++) {
            var layer = this.layers[i];
            layer.setSize(this.body.getWidth(),this.body.getHeight());
        }
        this.syncThumb(); 
    },
    syncThumb:function(e) {
        if (!this.body) return false;

        var xRatio =   Math.abs(this.body.getWidth() / (this.xMaxValue - this.xMinValue));
        var xOffset = (this.xyPosition[0] * xRatio) - (this.xMinValue * xRatio);
        var yratio =   Math.abs(this.body.getHeight() / (this.yMaxValue - this.yMinValue));
        var yOffset = (this.xyPosition[1] * yratio) - (this.yMinValue * yratio);
        
        this.setThumbPosition(xOffset,  this.body.getHeight()-yOffset,false);
    },
    
    setThumbPosition:function(offsetX,offsetY,animate) {
        if (Ext.isArray(offsetX)) { return this.setThumbPosition.call( this, offsetX[0], offsetX[1], offsetY ); }
  		// validate
		if (offsetX < 0) offsetX = 0
		if (offsetX > this.body.getWidth()) offsetX = this.body.getWidth();
		if (offsetY < 0) offsetY = 0
		if (offsetY > this.body.getHeight()) offsetY = this.body.getHeight();
        
        var posX = this.body.getLeft() + offsetX;
		var posY = this.body.getTop() + offsetY;

		// check if the arrow is bigger than the bar area
		if (this.sliderMode == "vertical") {
			posX = posX - (this.thumb.getWidth()/2 - this.el.getWidth()/2);
		} else {
			posX = posX - parseInt(this.thumb.getWidth()/2);
		}
		if (this.sliderMode == "horizontal") {
			posY = posY - (this.thumb.getHeight()/2 - this.body.getHeight()/2);
            if (posY > this.body.getBottom()) posY = this.body.getBottom() - (this.body.getHeight()/2+ this.thumb.getHeight()/2);
		} else {
			posY = posY - parseInt(this.thumb.getHeight()/2);
		}
        this.thumb.moveTo(posX,posY,animate);	
    },
    sliderDDHandler:function(e){
        this.thumbDD.constrainTo( this.body.dom, {top:-this.thumb.getHeight() / 2,left:-this.thumb.getWidth() / 2,right:-this.thumb.getWidth() / 2,bottom:-this.thumb.getHeight() / 2});
        var  p  = this.setMousePosition(e);
        
        this.setThumbPosition(p,false);
    },
    setMousePosition:function(e){
        if (e && e.xy) {
            var relativeX = 0;
            var relativeY = 0;
            
            if (e.xy[0] < this.body.getLeft())
    			relativeX = 0;
    		else if (e.xy[0] > this.body.getRight())
    			relativeX = this.body.getWidth();
    		else
    			relativeX = e.xy[0] - this.body.getLeft() ;
    
    		if (e.xy[1] < this.body.getTop())
    			relativeY = 0;
    		else if (e.xy[1] > this.body.getBottom())
    			relativeY = this.body.getHeight();
    		else
    			relativeY = e.xy[1] - this.body.getTop() ;
    			
            if (this.sliderMode == "vertical") relativeX = 0;
            if (this.sliderMode == "horizontal") relativeY = 0;

    		var newXValue = parseInt(relativeX / this.body.getWidth() * this.xMaxValue);
    		var newYValue =this.yMaxValue - parseInt(relativeY / this.body.getHeight() * this.yMaxValue);
    		
            newYValue = newYValue < 0 ? 0 : newYValue;
            
            this.xyPosition = [newXValue,newYValue];
            
    		if (this.xMaxValue == this.xMinValue) relativeX = 0;
    		if (this.yMaxValue == this.yMinValue) relativeY = 0;	
     	
            var newValue = this.xyPosition;
            var oldValue = this.xyPosition;
            newValue = this.sliderMode == "vertical" ? newValue[1] : newValue;
            newValue = this.sliderMode == "horizontal" ? newValue[0] : newValue;
            oldValue = this.sliderMode == "vertical" ? oldValue[1] : oldValue;
            oldValue = this.sliderMode == "horizontal" ? oldValue[0] : oldValue;
            
            this.fireEvent("change",this,newValue,oldValue);
            return [relativeX, relativeY];
        }
    },

    setValue:function(value) {
        if (value) {
            if(Ext.isArray(value) && this.sliderMode == "area") {
                this.xyPosition = value;
            }
            if (this.sliderMode == "vertical") {
                this.xyPosition[1] = Ext.isArray(value) ? parseInt(value[1]) : parseInt(value) ;
            } else if(this.sliderMode == "horizontal") {
                this.xyPosition[0] = Ext.isArray(value) ? parseInt(value[0]) : parseInt(value);;
            }
            this.syncThumb();
        }
    },
    getValue:function() {
        var newValue = this.xyPosition;
        newValue = this.sliderMode == "vertical" ? newValue[1] : newValue;
        newValue = this.sliderMode == "horizontal" ? newValue[0] : newValue;
        return newValue;
    },
    getBody:function() {
      return this.body;  
    },
    getLayers:function() {
        return this.layers;
    },
    getWidth:function() {
        return this.el.getWidth();
    },
    getHeight:function() {
        return this.el.getHeight();
    },
    moveTo:function(x,y) {
        this.el.moveTo(x,y,false);
    }
});


/**
 * Provides a mixer color modes.
 * @constructor
 * @param {Object} config
 */
Ext.ux.color.Modes = {
    hue:"h",
    saturation:"s",
    brightness:"b",
    red:"r",
    green:"g",
    blue:"b"
} ;


/**
 * @class Ext.ux.color.Mixer
 * @extends Ext.BoxComponent
 * <p>A popup colormixer picker. This class is used by the {@link Ext.ux.color.colorField colorfield} class
 * to allow browsing and selection of colors.</p>
 * @constructor
 * Create a new color Mixer
 * @param {Object} config The config object
 * @xtype colormixer
 */
Ext.ux.color.Mixer = Ext.extend(Ext.BoxComponent,{
    /**
     * @cfg {String} area slider color selection text.
     */
    selectorText:"Double Click or CTRL+Click for selection",
    itemCls:"x-cpm-mixer", //private
    /**
     * @cfg {String} colorMode.See {@link Ext.ux.color.Modes}
     * Set to colormode for colormixer picker(defaults to 'h')
     */
    colorMode:'h',
    /**
     * @cfg {Number} set slider thumb width
     */
    sliderWidth:16, //incomplate for thumb resize
    /**
     * @cfg {String} set default color. Defaults to "#000000"
     */
    defaultColor:"#000000",
    width:280,
    height:180,
    constructor:function(config) {
        config =config || {};
        Ext.apply(this,config);
        this.color = new Ext.ux.color.color(config.color || this.defaultColor);
        Ext.ux.color.Mixer.superclass.constructor.call(this,config);
    },
    
    initComponent:function() {
        this.addEvents(
            /**
             * @event change
             * Fires when a color is changed. Draging thumb on sliders.
             * @param {Ext.ux.colo.Mixer} this Mixer
             * @param {hex} hex The selected color
             * @param {Ext.ux.color.color} the selected color object. {@link Ext.ux.color.color}
             */
            'change',
            /**
             * @event select
             * Fires when a color is selected
             * @param {Ext.ux.colo.Mixer} this Mixer
             * @param {hex} hex The selected color
             * @param {Ext.ux.color.color} the selected color object. {@link Ext.ux.color.color}
             */
            'select',
            /**
             * @event changemode
             * Fires when a colormode is changed
             * @param {Ext.ux.colo.Mixer} this Mixer
             * @param {String} The changed colormode {@link Ext.ux.color.Modes}
             */
            'changemode'
        );
        
        Ext.ux.color.Mixer.superclass.initComponent.call(this);
    },
    onRender:function(ct,position) {
        this.autoEl = {
            tag: 'div',
            cls: this.itemCls
        };
        Ext.ux.color.Mixer.superclass.onRender.call(this,ct,position);
        
        this.areaSlider = new Ext.ux.color.Slider({
            enableTip:true,
            qTip:this.selectorText,
            itemCls:"x-cpm-slider-area",
            renderTo:this.el.dom
        });
        
        this.verticalSlider = new Ext.ux.color.Slider({
            itemCls:"x-cpm-slider-vertical",
            sliderMode:"vertical",
            thumbCls:"x-cpm-slider-vertical-thumb",
            renderTo:this.el.dom
        });
        
        this.on("resize",this.onResizeHandler,this);
        this.areaSlider.on("change",this.areaThumbHandler,this);
        this.areaSlider.on("select",function(slider,newPos,oldPos){
            this.updateColorByPosition(newPos);
            this.fireEvent("select",this,this.color.getHexColor(),this.color);
        },this);
        this.verticalSlider.on("change",this.sliderThumbHandler,this);
        this.setColorMode(this.colorMode);
    },

    onResizeHandler:function(mixer,w,h) {
        var eb = {
            h : this.el.getHeight(),
            w : this.el.getWidth(),
            t : this.el.getTop(),
            r : this.el.getRight(),
            b : this.el.getBottom(),
            l : this.el.getLeft(),
            pt: this.el.getPadding("t"),
            pr: this.el.getPadding("r"),
            pb: this.el.getPadding("b"),
            pl: this.el.getPadding("l")
        }
       
        this.verticalSlider.setSize(this.sliderWidth , (eb.h-(eb.pt+eb.pb)));
        this.verticalSlider.moveTo((eb.r-eb.pr-this.sliderWidth) , (eb.t + eb.pt));
        this.areaSlider.setSize(((eb.w-(eb.pl+eb.pr)) - (this.sliderWidth)) , (eb.h-(eb.pt+eb.pb)));
    },
    //private
    updateColorByPosition:function(newPos) {
       switch (this.colorMode) {
            case 'h':
                this.color.setHsv(this.color.h, newPos[0], newPos[1]);
            break;
            case 's':
                this.color.setHsv(newPos[0],this.color.s,newPos[1]);
            break;
            case 'v':
                this.color.setHsv(newPos[0],newPos[1],this.color.v);
            break;
            case 'r':
                this.color.setRgb(this.color.r,newPos[1], newPos[0]);
            break;
            case 'g':
                this.color.setRgb(newPos[1],this.color.g, newPos[0]);
            break;
            case 'b':
                this.color.setRgb(newPos[0], newPos[1],this.color.b);
            break;
        }  
    },
    //private
    areaThumbHandler:function(slider,newPos,oldPos) {
       this.updateColorByPosition(newPos);
        this.updateSlidersVisuals();
        this.fireEvent("change",this,this.color.getHexColor(),this.color);
    },
    //private
    sliderThumbHandler:function(slider,newPos,oldPos) {
        switch(this.colorMode) {
            case 'h':
                this.color.setHsv(newPos,this.color.s,this.color.v);
            break;
            case 's':
                this.color.setHsv(this.color.h,newPos,this.color.v);
            break;
            case 'v':
                this.color.setHsv(this.color.h,this.color.s,newPos);
            break;
            case 'r':
                this.color.setRgb(newPos,this.color.g,this.color.b);
            break;
            case 'g':
                this.color.setRgb(this.color.r,newPos,this.color.b);
            break;
            case 'b':
                this.color.setRgb(this.color.r,this.color.g,newPos);
            break;
        }
        this.updateSlidersVisuals();
        this.fireEvent("change",this,this.color.getHexColor(),this.color);
    },

    /**
     * Change mixer picker colormode.
     * @param {String} The color mode. See the {@link Ext.ux.color.Modes}
     * for details on supported values.
     */
    setColorMode:function(mode) {
        //RESET STYLES
        if (!this.verticalSlider || !this.areaSlider) return null;
        for (var i = 0; i < this.verticalSlider.layers.length; i++) {
            this.verticalSlider.layers[i].dom.className = "x-cpm-layer";
            this.verticalSlider.layers[i].setStyle("background-color","");
            this.verticalSlider.layers[i].setStyle("background-image","");
            this.verticalSlider.layers[i].clearOpacity();
            this.verticalSlider.layers[i].dom.style.filter = "";
        }
        for (var i = 0; i < this.areaSlider.layers.length; i++) {
            this.areaSlider.layers[i].dom.className = "x-cpm-layer";
            this.areaSlider.layers[i].setStyle("background-color","");
            this.areaSlider.layers[i].setStyle("background-image","");
            this.areaSlider.layers[i].clearOpacity();
            this.areaSlider.layers[i].dom.style.filter = "";
        }
        
        this.colorMode = mode;
        
        function applyAlphaImageLoader(layer) { //FOR IE
            var url = layer.getStyle("background-image").toString();
            if (!Ext.isEmpty(url)) {
                var m = url.match(/^url*\(*"*([A-Za-z0-9:\/\-.]+)"*\)*/im);
                if (m != null) {
                    layer.setStyle("background-image","url(images/blank.gif)");
                    layer.setStyle("filter","progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+m[1]+"', sizingMethod='scale')"); 
                    layer.dom.filters.item("DXImageTransform.Microsoft.AlphaImageLoader").apply();
                }
            }
            
        }
        
        switch (mode) {
       	    case 'h':
                //map mode
                this.areaSlider.layers[0].setStyle("background-color",this.color.getHexColor());
                this.areaSlider.layers[1].addClass("x-cpm-map-hue");
                //slider mode
                this.verticalSlider.layers[0].addClass("x-cpm-bar-hue");
                
                if (Ext.isIE) {
                    applyAlphaImageLoader(this.verticalSlider.layers[0]);
                    applyAlphaImageLoader(this.areaSlider.layers[1]);              
                }
                
                this.areaSlider.xMaxValue = 100;
				this.areaSlider.yMaxValue = 100;
				this.verticalSlider.yMaxValue = 360;
            break;
            case 's':
                this.areaSlider.layers[0].addClass("x-cpm-map-saturation-overlay");
                this.areaSlider.layers[1].addClass("x-cpm-map-saturation");
                
                this.verticalSlider.layers[0].setStyle("background-color",this.color.getHexColor());
                this.verticalSlider.layers[0].addClass("x-cpm-bar-saturation");
                
                if (Ext.isIE) {
                    applyAlphaImageLoader(this.verticalSlider.layers[0]);
                    applyAlphaImageLoader(this.areaSlider.layers[0]);
                    applyAlphaImageLoader(this.areaSlider.layers[1]);
                }
                
                this.areaSlider.xMaxValue = 360;
				this.areaSlider.yMaxValue = 100;
				this.verticalSlider.yMaxValue = 100;
            break;
            case 'v' :
                this.areaSlider.layers[0].setStyle("background-color","#000000");
                this.areaSlider.layers[1].addClass("x-cpm-map-brightness");
                
                this.verticalSlider.layers[0].addClass("x-cpm-bar-brightness");
                
                if (Ext.isIE) {
                   applyAlphaImageLoader(this.verticalSlider.layers[0]);
                   applyAlphaImageLoader(this.areaSlider.layers[1]);
                }
                
                this.areaSlider.xMaxValue = 360;
				this.areaSlider.yMaxValue = 100;
				this.verticalSlider.yMaxValue = 100;
            break;
            case 'r':
                this.areaSlider.layers[0].addClass("x-cpm-map-red-min");
                this.areaSlider.layers[1].addClass("x-cpm-map-red-max");
                
                this.verticalSlider.layers[0].addClass("x-cpm-bar-red-bl");
                this.verticalSlider.layers[1].addClass("x-cpm-bar-red-br");
                this.verticalSlider.layers[2].addClass("x-cpm-bar-red-tr");
                this.verticalSlider.layers[3].addClass("x-cpm-bar-red-tl");
                
                if (Ext.isIE) {
                    applyAlphaImageLoader(this.verticalSlider.layers[0]);
                    applyAlphaImageLoader(this.verticalSlider.layers[1]);
                    applyAlphaImageLoader(this.verticalSlider.layers[2]);
                    applyAlphaImageLoader(this.verticalSlider.layers[3]);
                    applyAlphaImageLoader(this.areaSlider.layers[0]);
                    applyAlphaImageLoader(this.areaSlider.layers[1]);
                }
            break;
            case 'g':
                this.areaSlider.layers[0].addClass("x-cpm-map-green-min");
                this.areaSlider.layers[1].addClass("x-cpm-map-green-max");
                
                this.verticalSlider.layers[0].addClass("x-cpm-bar-green-bl");
                this.verticalSlider.layers[1].addClass("x-cpm-bar-green-br");
                this.verticalSlider.layers[2].addClass("x-cpm-bar-green-tr");
                this.verticalSlider.layers[3].addClass("x-cpm-bar-green-tl");
                
                if (Ext.isIE) {
                    applyAlphaImageLoader(this.verticalSlider.layers[0]);
                    applyAlphaImageLoader(this.verticalSlider.layers[1]);
                    applyAlphaImageLoader(this.verticalSlider.layers[2]);
                    applyAlphaImageLoader(this.verticalSlider.layers[3]);
                    applyAlphaImageLoader(this.areaSlider.layers[0]);
                    applyAlphaImageLoader(this.areaSlider.layers[1]);
                }
            break;
            case 'b':
                this.areaSlider.layers[0].addClass("x-cpm-map-blue-min");
                this.areaSlider.layers[1].addClass("x-cpm-map-blue-max");
                
                this.verticalSlider.layers[0].addClass("x-cpm-bar-blue-bl");
                this.verticalSlider.layers[1].addClass("x-cpm-bar-blue-br");
                this.verticalSlider.layers[2].addClass("x-cpm-bar-blue-tr");
                this.verticalSlider.layers[3].addClass("x-cpm-bar-blue-tl");
                
                if (Ext.isIE) {
                    applyAlphaImageLoader(this.verticalSlider.layers[0]);
                    applyAlphaImageLoader(this.verticalSlider.layers[1]);
                    applyAlphaImageLoader(this.verticalSlider.layers[2]);
                    applyAlphaImageLoader(this.verticalSlider.layers[3]);
                    applyAlphaImageLoader(this.areaSlider.layers[0]);
                    applyAlphaImageLoader(this.areaSlider.layers[1]);
                }
            break;
            
        }
        if (mode == "h" || mode == "s" || mode == "v") { //set min values
            this.areaSlider.xMinValue = 0;
			this.areaSlider.yMinValue = 0;				
			this.verticalSlider.yMinValue = 0;
        } else if (mode == "r" || mode == "g" || mode == "b") { //set min values
            this.areaSlider.xMinValue = 0;
			this.areaSlider.yMinValue = 0;				
			this.verticalSlider.yMinValue = 0;
            
            this.areaSlider.xMaxValue = 255;
			this.areaSlider.yMaxValue = 255;				
			this.verticalSlider.yMaxValue = 255;
            
        }
        
        this.syncThumbsBycolorMode();
        this.updateSlidersVisuals();
        this.fireEvent("changemode",this,mode);
    },
    
    //private
    syncThumbsBycolorMode: function() {
		var sliderValue = 0;
		switch(this.colorMode) {
			case 'h':
				sliderValue = this.color.h;
				break;
			
			case 's':
				sliderValue =  this.color.s;
				break;
				
			case 'v':
				sliderValue =  this.color.v;
				break;
				
			case 'r':
				sliderValue =  this.color.r;
				break;
			
			case 'g':
				sliderValue =  this.color.g;
				break;
				
			case 'b':
				sliderValue =  this.color.b;
				break;				
		}	
		
		this.verticalSlider.xyPosition[1] = sliderValue;
		this.verticalSlider.syncThumb();
		// color map
		var mapXValue = 0;
		var mapYValue = 0;
		switch(this.colorMode) {
			case 'h':
				mapXValue = this.color.s;
				mapYValue =  this.color.v;
				break;
				
			case 's':
				mapXValue = this.color.h;
				mapYValue =  this.color.v;
				break;
				
			case 'v':
				mapXValue = this.color.h;
				mapYValue =  this.color.s;
				break;
				
			case 'r':
				mapXValue = this.color.b;
				mapYValue =  this.color.g;
				break;
				
			case 'g':
				mapXValue = this.color.b;
				mapYValue =  this.color.r;
				break;
				
			case 'b':
				mapXValue = this.color.r;
				mapYValue =  this.color.g;
				break;				
		}
		this.areaSlider.xyPosition = [mapXValue,mapYValue];
		this.areaSlider.syncThumb();
	},
    //private
    updateSlidersVisuals:function() {
        var hValue = 0;
		var vValue = 0;
                
        function setRGBAlpha(slider,hValue,vValue) {
            	var hPer = parseInt(hValue) /255;
				var vPer = parseInt(vValue) /255;
				
				var hPerRev = (255-parseInt(hValue))/255;
				var vPerRev = (255-parseInt(vValue))/255;	

				slider.layers[0].setOpacity((vPerRev>hPerRev) ? hPerRev : vPerRev);
                slider.layers[1].setOpacity((vPerRev>hPer) ? hPer : vPerRev);
                slider.layers[2].setOpacity((vPer>hPer) ? hPer : vPer);
                slider.layers[3].setOpacity((vPer>hPerRev) ? hPerRev : vPer);
                			
        }
        
        switch(this.colorMode) {
            case 'h':
                //map
                var color = new Ext.ux.color.color({h:this.color.h, s:100, v:100});
                this.areaSlider.layers[0].setStyle("background-color",color.getHexColor());
	        break;
            case 's':
                //map
                this.areaSlider.layers[1].setOpacity(this.color.s/100);
                //slider
                var color = new Ext.ux.color.color({h:this.color.h, s:100, v:this.color.v});
                this.verticalSlider.layers[0].setStyle("background-color",color.getHexColor());
            break;
            case 'v':
                this.areaSlider.layers[1].setOpacity(this.color.v/100);
                var color = new Ext.ux.color.color({h:this.color.h, s:this.color.s, v:100});
                this.verticalSlider.layers[0].setStyle("background-color",color.getHexColor());
            break;
            case 'r':
                this.areaSlider.layers[1].setOpacity(this.color.r/255);
                setRGBAlpha(this.verticalSlider,this.color.b,this.color.g);
            break;
            case 'g':
                this.areaSlider.layers[1].setOpacity(this.color.g/255);
                setRGBAlpha(this.verticalSlider,this.color.b,this.color.r);
            break;
            case 'b':
                this.areaSlider.layers[1].setOpacity(this.color.b/255);
                setRGBAlpha(this.verticalSlider,this.color.r,this.color.g);
            break;
        }
    },
    
    /**
     * Set Mixer color value.
     * @param {Mixed} . See the {@link Ext.ux.color.color} config
     * for details on supported values.
     * @return {Object} this Mixer
     */
    setColor:function(v) {
        this.value = v;
        if(this.rendered){
            this.color = new Ext.ux.color.color(Ext.isEmpty(v) ? '#000000' : v);
            this.value = this.color.getHexColor();
            
            this.syncThumbsBycolorMode();
            this.updateSlidersVisuals();
            this.fireEvent("change",this,this.color.getHexColor(),this.color);
        }
        return this;
    },
    
    /**
     * Get HTML color value from this color object.
     * @return {String} HTML hexedecimal color value
     */
    getColorHexValue:function() {
        return this.color.getHexColor();
    },
    /**
     * Get color object.
     * @return {Object} The color class. See the {@link Ext.ux.color.color} config
     * for details on supported values.
     */
    getColor:function(){
        return this.color;
    },
    
    /**
    * Get area Slider object.
    * @return {Object} The Slider class. See the {@link Ext.ux.color.Slider} config
    * for details on supported values.
    */
    getAreaSlider:function() {
        return this.areaSlider;
    },
    /**
    * Get vertical Slider object.
    * @return {Object} The Slider class. See the {@link Ext.ux.color.Slider} config
    * for details on supported values.
    */
    getVerticalSlider:function() {
        return this.verticalSlider;
    }
    
});
Ext.reg('colormixer', Ext.ux.color.Mixer);




/**
 * @class Ext.ux.color.Box
 * @extends Ext.BoxComponent
 * <p>Help for other components visuality and interactions. This class is used by the {@link Ext.ux.color.Palette} class
 * to allow browsing and selection of colors.</p>
 * @constructor
 * Create a new color Box
 * @param {Object} config The config object
 */
Ext.ux.color.Box = Ext.extend(Ext.BoxComponent,{
    itemCls:"x-cpm-box",
    disabledCls:"x-cpm-box-disabled",
    overCls:"x-cpm-box-over",
    value:'',
    color:null,
    height:50,
    width:50,
    enableTip:false,
    qTip:"",
    disabled:false,
    constructor:function(config) {
      config = config || {};
      config.value = config.value || "#000000";
      Ext.apply(this,config);
        Ext.ux.color.Box.superclass.constructor.call(this,config);
    },
    initComponent:function() {
        this.addEvents(
            'select',
            'mouseover'
        );
        Ext.ux.color.Box.superclass.initComponent.call(this);
        this.color = new Ext.ux.color.color(this.value);
    },
    onRender:function(ct,position) {
        this.autoEl = {
            tag: 'div',
            cls: this.itemCls,
            qtip:this.enableTip ? this.qTip : ""
        };
        
        Ext.ux.color.Box.superclass.onRender.call(this,ct,position);
        this.setColor(this.value);
        this.el.on("click",this.selectHandler,this);
        this.el.on("mouseover",this.mouseOverHandler,this);
        this.el.on("mouseout",this.mouseOutHandler,this);
        if (this.disabled == true) this.setDisabled(true);
    },
    selectHandler:function(e,t,o) {
        if (this.disabled == true) return ;
        this.fireEvent("select",this,this.value,this.color,e);
    },
    mouseOverHandler:function(e,t,o) {
        if (this.disabled == true) return ;
        this.addClass(this.overCls);
        this.fireEvent("mouseover",this,this.value,this.color,e);
    },
    mouseOutHandler:function(e,t,o) {
        this.removeClass(this.overCls);
    },
    
    /**
     * Set Mixer color value.
     * @param {Mixed} . See the {@link Ext.ux.color.color} config
     * for details on supported values.
     * @return {Object} this Box
     */
    setColor:function(v) {
        if(this.rendered){
            this.color = new Ext.ux.color.color(Ext.isEmpty(v) ? '#000000' : v);
            this.value = this.color.getHexColor();
            if (this.rendered) {
                this.el.setStyle("background-color",this.value);
            }
        }
        return this;
    },
    /**
     * @return {String} this hex color value
     */
    getValue : function(){
        return this.value;
    },
    /**
     * @return {Object} this color object. See the {@link Ext.ux.color.color} config
     * for details on supported values.
     */
    getColor: function() {
        return this.color;
    },
    /**
     * Set Box disabled.
     * @param {Boolean}. Disable this for mouse events.
     */
    setDisabled:function(disabled) {
        if (disabled == true) {
            this.disabled = true;
            this.addClass(this.disabledCls);
        } else {
            this.disabled = false;
            this.removeClass(this.disabledCls);
        }
    }
});




/**
 * @class Ext.ux.color.Palette
 * @extends Ext.Container
 * <p>A popup colorpalette picker. This class is used by the {@link Ext.ux.color.colorField colorfield} class
 * to allow browsing and selection of colors.</p>
 * @constructor
 * Create a new color Palette
 * @param {Object} config The config object
 * @xtype colorpalette
 */
Ext.ux.color.Palette = Ext.extend(Ext.BoxComponent, {
    width:272,
    height:183,
    /**
     * @cfg {Boolean} Set color half count for palette boxes. Defaults to false 
     */
    halfMode:false,
    /**
     * @cfg {Number} Each box width property. Inconplate on menu resize. Defaults to 15
     */
    boxWidth:13,
    /**
     * @cfg {Number} Each box height property. Inconplate on menu resize. Defaults to 15
     */
    boxHeight:13,
    /**
     * @cfg {Array} Set non selectable color boxes. Defaults to empty
     */
    disabledColors:[],
    /**
     * @cfg {Array} Set range for color counts.
     */
    colorRange : ['00','33','66','99','CC','FF'],
    colorRe: /(?:^|\s)color-(.{6})(?:\s|$)/,
    clickEvent:"click",
    disabledColorText:"Disabled",
    constructor:function(config) {
      config = config || {};
      if (Ext.isEmpty(config.colorRange) || !Ext.isArray(config.colorRange)) config.colorRange = this.colorRange;
      Ext.apply(this,config);

      Ext.ux.color.Palette.superclass.constructor.call(this,config);
    },
    initComponent:function() {
        this.addEvents(
            /**
             * @event select
             * Fires when a color box item is selected
             * @param {Ext.ux.colo.palette} this Palette
             * @param {hex} hex The selected color
             * @param {Ext.ux.color.color} the selected color object. {@link Ext.ux.color.color}
             */
            'select',
            /**
             * @event mouseover
             * Fires when mouse on box over.
             * @param {Ext.ux.colo.Palette} this Palette
             * @param {hex} hex The selected color
             * @param {Ext.ux.color.color} The mouseover box color object. {@link Ext.ux.color.color}
             */
            'mouseover'
        );
        
        this.colorData = [];
        for (i=0;i<this.colorRange.length;i++) {
    		for (j=0;j<this.colorRange.length;j++) {
    					for (k=0;k<this.colorRange.length; this.halfMode ? k = k+2 : k++) {
    					    var color = this.colorRange[i]+this.colorRange[j]+this.colorRange[k];
                            var disabled = this.disabledColors.contains(color,true);
                            this.colorData.push({color:color,disabled:disabled,w:this.boxWidth,h:this.boxHeight});
    					}
    		}
        }
        if (this.halfMode) {
            var disabled = this.disabledColors.contains("FFFFFF",true);
            
            this.colorData.splice(this.colorData.length-1,1,{
                color:"FFFFFF",
                disabled:disabled,
                w:this.boxWidth,
                h:this.boxHeight
            });
        }
        
        Ext.ux.color.Palette.superclass.initComponent.call(this);
    },
    onRender:function(ct,position) {
        this.autoEl =  {
            tag:"div",
            cls:"x-cpm-palette"
        };

        var clickEvent = this.clickEvent;
            
        Ext.ux.color.Palette.superclass.onRender.call(this,ct,position);
        
        this.tpl = new Ext.XTemplate('<tpl for="."><a href="#" class="color-{color}" hidefocus="on" <tpl if="disabled == true"> ext:qtip="'+this.disabledColorText+'"</tpl>><em><span <tpl if="disabled == true">class="disabled-color"</tpl> style="background-color:#{color};width:{w}px;height:{h}px;" unselectable="on">&#160;</span></em></a></tpl>');
        this.tpl.overwrite(this.el,this.colorData);
        
        this.mon(this.el, clickEvent, this.handleClick, this, {delegate: 'a'});
        this.mon(this.el, "mouseover", this.onItemMouseOver, this, {delegate: 'a'});
        
        // always stop following the anchors
        if(clickEvent != 'click'){
            this.mon(this.el, 'click', Ext.emptyFn, this, {delegate: 'a', stopEvent: true});
        }
    },
    // private
    afterRender : function(){
        var me = this,
            value;
            
        Ext.ux.color.Palette.superclass.afterRender.call(this);
        if (me.value) {
           me.setColor(value);
        }
    },
    onItemMouseOver:function(e,t) {
        var me = this,
            color;

        e.stopEvent();
        if (!me.disabled) {
            var value = t.className.match(me.colorRe)[1];
            if (!me.disabledColors.contains(value,true)) {
                var color = new Ext.ux.color.color(value);
                this.fireEvent("mouseover",t,color.getHexColor(),color,e);
            }
        }
    },
    handleClick:function(event,target) {
      var me = this,
            color;
            
        event.stopEvent();
        if (!me.disabled) {
            var value = target.className.match(me.colorRe)[1];
            if (!me.disabledColors.contains(value,true)) {
                var color = new Ext.ux.color.color(value);
                me.setColor(value);
                this.fireEvent("select",target,color.getHexColor(),color,event);
            }
        }
    },
    /**
     * Set Palette color value.
     * @param {Mixed} . See the {@link Ext.ux.color.color} config
     * for details on supported values.
     * @return {Object} this Palette
     */
    setColor:function(v) {
        if (!Ext.isEmpty(v)) {
            var color = new Ext.ux.color.color(v);
            this.value = color.getHexColor();
            this.updateColorBoxVisuals(color.hex);
            return this;
        }
    },
    //private
    updateColorBoxVisuals:function(value) {
        //remove old selection
        var results = this.getEl().query("a.selected",this);
        for (var i = 0; i < results.length; i++) {
            Ext.get(results[i]).removeClass("selected");
        }
        
         var queryString = String.format(".color-{0}",value);
         var results = this.getEl().query(queryString,this);
         if (Ext.isArray(results) && results.length > 0) {
            Ext.get(results[0]).addClass("selected");
         }
    },
    /**
     * @return {String} The hexedecimal color value for HTML.
     */
    getColor:function() {
        return this.value;
    },
    setDisabledColor:function(color,disabled) {
        if (!Ext.isEmpty(color)) {
            color = new Ext.ux.color.color(color);
            var queryString = String.format(".color-{0} em span",color.hex);
            var results = this.getEl().query(queryString,this);
            
            if(Ext.isArray(results) && results.length > 0) {
                var boxEl = Ext.get(results[0]);
                if (disabled)  {
                    this.disabledColors.push(color.hex);
                    boxEl.addClass("disabled-color");
                    if (this.enableTip) {
                        if (!Ext.isEmpty(this.disabledColorText)) {
                            Ext.QuickTips.register({
                                target: boxEl,
                                text: this.disabledColorText,
                                dismissDelay: 2000
                            });
                        }
                    }
                } else {
                    Ext.Array.remove(this.disabledColors,color.hex);
                    boxEl.removeClass("disabled-color");
                    Ext.QuickTips.unregister(boxEl);
                }
                   
            }
        }
    },
    isDisabledColor:function(value) {
        if (!Ext.isEmpty(value)) {
            var color = new Ext.ux.color.color(value);
            return this.disabledColors.contains(color.hex);
        }
    }
    
});
Ext.reg('colorpalette', Ext.ux.color.Palette);



/**
 * @class Ext.ux.color.Panel
 * @extends Ext.Panel
 * <p>A popup colorpanel picker. This class is used by the {@link Ext.ux.color.colorField colorfield} and {@link Ext.ux.color.window} class
 * to allow browsing and selection of colors.</p>
 * @constructor
 * Create a new color Panel
 * @param {Object} config The config object
 * @xtype colorpanel
 */
Ext.ux.color.Panel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Boolean} split
     * <tt>true</tt> to create a {@link Ext.layout.BorderLayout.SplitRegion SplitRegion}
     */
    split:true,
    /**
     * @cfg {Boolean} collapsed
     * Set collapsed form panel.
     */
    collapsed:false,
    //private
    layout:"border",
    width:280,
    height:210,
    /**
     * @cfg {String} colorMode.See {@link Ext.ux.color.Modes}
     * Set to colormode for colorpanel picker(defaults to 'h')
     */
    colorMode:"h",
    constructor:function(config) {
        config = config || {};
        Ext.apply(this,config);
        
        Ext.ux.color.Panel.superclass.constructor.call(this,config);
    },
    initComponent:function() {
        this.addEvents(
            /**
             * @event change
             * Fires when mixer color changed
             * @param {Ext.ux.color.Panel} this Panel
             * @param {hex} hex The selected color
             * @param {Ext.ux.color.color} the changed color object. {@link Ext.ux.color.color}
             */
            'change',
            /**
             * @event select
             * Fires when a color box item or on mixer selected
             * @param {Ext.ux.color.Panel} this Panel
             * @param {hex} hex The selected color
             * @param {Ext.ux.color.color} the selected color object. {@link Ext.ux.color.color}
             */
            'select'
        );
        
        this.layout = "border"; //constraint
        this.mixer = new Ext.ux.color.Mixer({
            region:"center",
            listeners:{
                change:{
                    scope:this,
                    fn:function(mixer,hex,color) {
                        this.disableFormEvents = true;
                        this.controlPanel.getForm().setValues(color);
                        this.colorValueBox.setColor(hex);
                        this.fireEvent("change",this,hex,color);
                        this.disableFormEvents = false;
                    }
                },
                changemode:{
                    scope:this,
                    fn:function(mixer,mode) {
                        this.controlPanel.cascade(function(o){
                            if (o.itemId && o.itemId == mode) {
                                o.toggle(true);
                            }
                        },this);
                    }
                },
                select:{
                    scope:this,
                    fn:function(mixer,hex,color) {
                        this.fireEvent("select",this,hex,color);
                    }
                }
            }
        });
        
        this.colorBox = new Ext.ux.color.Box ({
                        value:this.mixer.getColor().getHexColor(),
                        style:"margin-bottom:5px;",
                        height:35,
                        enableTip:true,
                        qTip:"return to initial color",
                        listeners:{
                            select:{
                                scope:this,
                                fn:function(box,hex,color) {
                                    this.mixer.setColor(hex);
                                }
                            }
                        }
                    });
        
        this.colorValueBox = new Ext.ux.color.Box ({
                        value:this.mixer.getColor().getHexColor(),
                        style:"margin-bottom:5px;",
                        height:35,
                        enableTip:true,
                        qTip:"set initical color",
                        listeners:{
                            select:{
                                scope:this,
                                fn:function(box,hex,color) {
                                    this.colorBox.setColor(hex);
                                }
                            }
                        }
                    });
                    
        this.controlPanel = new Ext.form.FormPanel({
            region:"east",
            width:105,
            maxWidth:110,
            minWidth:105,
            border:false,
            header:false,
            split:this.split || false,
            collapsible:true,
            collapseMode:"mini",
            collapsed:this.collapsed || false,
            enableKeyEvents:true,
            bodyStyle:"padding:3px",
            items:[{
                xtype:"container",
                layout:"column",
                items:[{
                    xtype:"container",
                    border:false,
                    columnWidth:0.5,
                    items:[this.colorBox,{
                        xtype:"panel",
                        layout:"form",
                        labelWidth: 10,
                        border:false,
                        defaults:{
                            width: 30,
                            allowBlank: false,
                            allowDecimals: false,
                            allowNegative: false,
                            enableKeyEvents:true,
                            maxValue: 100,
                            minValue: 0,
                            selectOnFocus:true,
                            listeners:{
                                change:this.hsvValueChangeHandler.createDelegate(this),
                                keyup:this.hsvValueKeyDownHandler.createDelegate(this)
                            }
                        },
                        items:[{
                            xtype: 'numberfield',
                            maxValue: 360,
                            minValue: 0,
                            fieldLabel: 'H',
                            name:"h"
                        },{
                            xtype: 'numberfield',
                            maxValue: 100,
                            minValue: 0,
                            fieldLabel: 'S',
                            name:"s"
                        },{
                            xtype: 'numberfield',
                            maxValue: 100,
                            minValue: 0,
                            fieldLabel: 'V',
                            name:"v"
                        }]
                    }]
                },{
                    xtype:"container",
                    columnWidth:0.5,
                    border:false,
                    items:[this.colorValueBox,{
                        xtype:"panel",
                        layout:"form",
                        labelWidth: 10,
                        border:false,
                        defaults:{
                            width: 30,
                            allowBlank: false,
                            allowDecimals: false,
                            allowNegative: false,
                            enableKeyEvents:true,
                            maxValue: 100,
                            minValue: 0,
                            selectOnFocus:true,
                            listeners:{
                                change:this.rgbValueChangeHandler.createDelegate(this),
                                keyup:this.rgbValueKeyDownHandler.createDelegate(this)
                            }
                        },
                        items:[{
                            xtype: 'numberfield',
                            maxValue: 255,
                            minValue: 0,
                            fieldLabel: 'R',
                            name:"r"
                        },{
                            xtype: 'numberfield',
                            maxValue: 255,
                            minValue: 0,
                            fieldLabel: 'G',
                            name:"g"
                        },{
                            xtype: 'numberfield',
                            maxValue: 255,
                            minValue: 0,
                            fieldLabel: 'B',
                            name:"b"
                        }]
                    }]
                },{
                    xtype: 'container',
                    layout: 'form',
                    border: false,
                    columnWidth: 1,
                    labelWidth: 10,
                    items: [
                        {
                            xtype: 'textfield',
                            anchor: '98%',
                            fieldLabel: '#',
                            name:"hex",
                            allowBlank: false,
                            enableKeyEvents:true,
                            selectOnFocus:true,
                            vtype:"hexcolor",
                            listeners:{
                                change:this.hexValueChangeHandler.createDelegate(this),
                                keyup:this.hexValueKeyDownHandler.createDelegate(this)
                            }
                        },{
                            xtype:"container",
                            height:30,
                            layout:"hbox",
                            layoutConfig:{
                              align:"middle",
                              pack:"center"  
                            },
                            defaults:{
                              enableToggle:true,
                              scope:this,
                              handler:this.changeColorModeHandler,
                              xtype:"button",
                              toggleGroup:"colorModeGroup" + Ext.id(),
                              width:15
                            },
                            
                            items:[{
                                    text:"H",
                                    itemId:"h"
                                },{
                                    text:"S",
                                    itemId:"s"
                                },{
                                    text:"V",
                                    itemId:"v"
                                },{
                                    text:"R",
                                    itemId:"r"
                                },{
                                    text:"G",
                                    itemId:"g"
                                },{
                                    text:"B",
                                    itemId:"b"
                            }]
                        }
                    ]
                }]
            }]
        });
        
        this.items = [this.mixer,this.controlPanel];
        
        Ext.ux.color.Panel.superclass.initComponent.call(this);
    },
    afterRender:function(){
        Ext.ux.color.Panel.superclass.afterRender.call(this);
        this.controlPanel.getForm().setValues(this.mixer.getColor());
        this.mixer.setColorMode(this.colorMode);
    },
    //private
    changeColorModeHandler:function(btn) {
        this.mixer.setColorMode(btn.text.toLowerCase());
    },
    //private
    hsvValueChangeHandler:function(field,value,oldValue) {
        var h = field.name == "h" ? value : this.controlPanel.getForm().findField("h").getValue();
        var s = field.name == "s" ? value :this.controlPanel.getForm().findField("s").getValue();
        var v = field.name == "v" ? value :this.controlPanel.getForm().findField("v").getValue(); 
        if (this.controlPanel.getForm().isValid() ) {
            this.mixer.setColor({h:h,s:s,v:v});
        }
    },
    //private
    hsvValueKeyDownHandler:function(f,e) {
        var h = f.name == "h" ? e.target.value : this.mixer.getColor().h;
        var s = f.name == "s" ? e.target.value : this.mixer.getColor().s;
        var v = f.name == "v" ? e.target.value : this.mixer.getColor().v;
        if (this.controlPanel.getForm().isValid() ) {
            this.mixer.setColor({h:h,s:s,v:v});
        }
    },
    //private
    rgbValueChangeHandler:function(field,value,oldValue) {
        var r = field.name == "r" ? value : this.controlPanel.getForm().findField("r").getValue();
        var g = field.name == "g" ? value :this.controlPanel.getForm().findField("g").getValue();
        var b = field.name == "b" ? value :this.controlPanel.getForm().findField("b").getValue(); 
        if (this.controlPanel.getForm().isValid() ) {
            this.mixer.setColor({r:r,g:g,b:b});
        }
    },
    //private
    rgbValueKeyDownHandler:function(f,e) {
        var r = f.name == "r" ? e.target.value : this.mixer.getColor().r;
        var g = f.name == "g" ? e.target.value : this.mixer.getColor().g;
        var b = f.name == "b" ? e.target.value : this.mixer.getColor().b;
        if (this.controlPanel.getForm().isValid() ) {
            this.mixer.setColor({r:r,g:g,b:b});
        }
    },
    //private
    hexValueChangeHandler:function(field,value,oldValue) {
        if (this.controlPanel.getForm().isValid() ) {
            this.mixer.setColor("#" + value);
        }
    },
    //private
    hexValueKeyDownHandler:function(f,e) {
        if (this.controlPanel.getForm().isValid() ) {
            this.mixer.setColor("#" + e.target.value);
        }
    },
    /**
     * Set Panel color value.
     * @param {Mixed} . See the {@link Ext.ux.color.color} config
     * for details on supported values.
     * @return {Object} this Panel
     */
    setColor:function(value) {
        if (!Ext.isEmpty(value)) {
            this.mixer.setColor(value);
            this.colorValueBox.setColor(this.mixer.getColor().getHexColor());
            this.colorBox.setColor(this.mixer.getColor().getHexColor());
            return this;
        }
        
    },
    /**
     * Change panel mixer colormode.
     * @param {String} The color mode. See the {@link Ext.ux.color.Modes}
     * for details on supported values.
     */
    setColorMode:function(mode) {
      if (!Ext.isEmpty(mode)) {
        this.colorMode = mode;
        this.mixer.setColorMode(mode);
      }  
    },
    /**
     * @return {String} The color mode. See the {@link Ext.ux.color.Modes}
     * for details on supported values.
     */
    getColorMode:function() {
        return this.colorMode;
    },
    /**
     * @return {color} this mixer color.See the {@link Ext.ux.color.color} config
     * for details on supported values.
     */
    getColor:function(){
        return this.mixer.getColor();
    },
    /**
     * @return {Mixer} this mixer.See the {@link Ext.ux.color.color.Mixer} config
     * for details on supported values.
     */
    getMixer:function() {
        return this.mixer;
    },
    /**
     * @return {BasicForm} this Ext.form.BasicForm.See the {@link Ext.form.BasicForm} config
     * for details on supported values.
     */
    getForm:function() {
        return this.controlPanel.getForm();
    }
});
Ext.reg('colorpanel', Ext.ux.color.Panel);



/**
 * @class Ext.ux.color.Picker
 * @extends Ext.BoxComponent
 * <p>A popup colorpicker. This class is used by the {@link Ext.ux.color.colorField colorfield} class
 * to allow browsing and selection of colors.</p>
 * @constructor
 * Create a new color Picker 
 * @param {Object} config The config object
 * @xtype colorpicker2
 */
Ext.ux.color.Picker = Ext.extend(Ext.BoxComponent, {
    itemCls:"x-cpm-picker",
    width:272,
    height:208,
    draggable:false, //incomplate
    transparentOnDrag:true, //incomplate
    dragTransparency:0.5, //incomplate
    /**
     * @cfg {Mixed} color
     * Set initial color. See {@link Ext.ux.color.color} config
     * for details on supported values. Defaults to "#FFFFFF"
     */
    value:"#FFFFFF",
    /**
     * @cfg {Boolean} allow show/hide hex value on header. Defaults to "true"
     */
    showHexValue:true,
    /**
     * @cfg {Boolean} allow show/hide preview color box. Defaults to "true"
     */
    showPrevBox:true,
    /**
     * @cfg {Boolean} allow picking on pickerArea. Defaults to "true"
     */
    enablePick:true,
    /**
     * @cfg {String} colorMode.See {@link Ext.ux.color.Modes}
     * Set to colormode for colorpanel picker(defaults to 'h')
     */
    colorMode:"h",
    /**
     * @cfg {Element} set color picking element. Such as background-color , color styles or image canvas pixel color.
     */
    pickerArea:Ext.getBody(),
    constructor:function(config) {
      config =config || {};
      Ext.ux.color.Picker.superclass.constructor.call(this,config);
    },
    initComponent:function() {
        this.addEvents(
            'select',
            'change'
        );
        Ext.ux.color.Picker.superclass.initComponent.call(this);
    },
    onRender:function(ct,position) {
        this.autoEl = {
            tag:"div",
            cls:this.itemCls
        }
        
        Ext.ux.color.Picker.superclass.onRender.call(this,ct,position);
        
        var dh = Ext.DomHelper;
        
        this.paletteContent = Ext.get(dh.append(this.el.dom,{tag:"div",cls:"x-cpm-palette-content"},true));
        this.paletteHeader = Ext.get(dh.append(this.paletteContent.dom,{tag:"div",cls:"x-cpm-palette-header"},true));
        this.paletteBody = Ext.get(dh.append(this.paletteContent.dom,{tag:"div",cls:"x-cpm-palette-body"},true));
        
        this.prevBox = Ext.get(dh.append(this.paletteHeader.dom,{tag:"div",cls:"x-cpm-prevbox"},true));
        this.hexBox = Ext.get(dh.append(this.paletteHeader.dom,{tag:"span",cls:"palette-header-hex"},true));
        this.showMixerBtn = Ext.get(dh.append(this.paletteHeader.dom,{tag:"div",cls:"x-cpm-mixer-button",html:"mixer"},true));
        this.backgroundPickerBtn = Ext.get(dh.append(this.paletteHeader.dom,{tag:"div",cls:"x-cpm-backpicker-button"},true));
        this.colorPickerBtn = Ext.get(dh.append(this.paletteHeader.dom,{tag:"div",cls:"x-cpm-colorpicker-button"},true));
        
        this.mixerContent = Ext.get(dh.append(this.el.dom,{tag:"div",cls:"x-cpm-mixer-content"},true));
        this.mixerBody = Ext.get(dh.append(this.mixerContent.dom,{tag:"div",cls:"x-cpm-mixer-body"},true));
        this.mixerFooter = Ext.get(dh.append(this.mixerContent.dom,{tag:"div",cls:"x-cpm-mixer-footer"},true));
        
        this.okBtn = Ext.get(dh.append(this.mixerFooter.dom,{tag:"button",cls:"x-cpm-button",html:"OK"},true));
        this.cancelBtn = Ext.get(dh.append(this.mixerFooter.dom,{tag:"button",cls:"x-cpm-button",html:"cancel"},true));
        
        
        if (this.draggable) {
            this.paletteHeader.addClass("x-cpm-dragable");
            this.dd = new Ext.dd.DD(this, 'pickerDDGroup');
            this.dd.onDrag = function(e){
                if (this.transparentOnDrag) {
                    this.el.setOpacity(this.dragTransparency);
                }
            }.createDelegate( this ); 
            this.dd.endDrag = function(e){
                if (this.transparentOnDrag) {
                    this.el.clearOpacity();
                }
            }.createDelegate( this ); 
                       
        }
        
        this.palette = new Ext.ux.color.Palette(Ext.applyIf({
            renderTo:this.paletteBody.dom,
            listeners:{
                mouseover:{
                    scope:this,
                    fn:function(box,hex,color){
                        this.prevBox.setStyle("background-color",hex);
                        this.hexBox.dom.innerHTML = hex;
                    }
                },
                select:{
                    scope:this,
                    fn:function(box,hex,color,e){
                        if (e.ctrlKey) {
                            this.mixerContent.slideIn("t",{duration:0.2});
                            (function(){
                                this.setColor(hex);
                            }.createDelegate(this)).defer(100);
                        } else {
                            this.fireEvent("select",this,hex,color);
                            this.setColor(hex);
                        }
                    }
                }
            }
        },this.initialConfig));
        
        this.mixerPanel = new Ext.ux.color.Panel(Ext.applyIf({
            renderTo:this.mixerBody.dom,
            listeners:{
                select:{
                    scope:this,
                    fn:function(box,hex,color,e){
                         this.value = hex;
                         this.updatePreviewValue(hex);
                         this.palette.setColor(hex);
                        this.fireEvent("select",this,hex,color);
                    }
                },
                change:{
                    scope:this,
                    fn:function(box,hex,color,e) {
                        this.fireEvent("change",this,hex,color);
                    }
                }
            }
        },this.initialConfig));
        
        
        this.mixerContent.setVisibilityMode(Ext.Element.DISPLAY);
        
        this.cancelBtn.on("click",function(btn){
            this.mixerContent.slideOut("t",{duration:0.2});
        },this);
        
        this.okBtn.on("click",function(btn){
            this.fireEvent("select",this,this.mixerPanel.getColor().getHexColor(),this.mixerPanel.getColor());
            this.value = this.mixerPanel.getColor().getHexColor();
            this.updatePreviewValue(this.value);
            this.palette.setColor(this.value);
            this.mixerContent.slideOut("t",{duration:0.2});
        },this);
        
        this.showMixerBtn.on("click",function(btn) {
            this.mixerContent.slideIn("t",{duration:0.2});
            (function(){
                this.setColor(this.value); //for update slider visuals
            }.createDelegate(this)).defer(100);
        },this);
        
        this.backgroundPickerBtn.on("click",function(){
            this.pickerArea.addClass("x-cpm-over-cursor");
            this.backgroundPickerBtn.addClass("x-cpm-onpick-button");
            this.backgroundPickerEvents("on");
        },this);
        
        
        this.colorPickerBtn.on("click",function(e,btn){
            this.pickerArea.addClass("x-cpm-over-cursor");
            this.colorPickerBtn.addClass("x-cpm-onpick-button");
            this.colorPickerEvents("on");
        },this);

        
        this.on("resize",this.onResizeHandler,this);
        this.el.on("mouseleave",function(e,t,o){
            this.updatePreviewValue(this.value);
        },this);
        this.updateVisuals();
        
        this.setColor(this.value);
    },
    colorPickerEvents:function(method) {
        this.pickerArea[method]('mousemove', this.getColorPickingHandler, this);
        this.pickerArea[method]('mousedown', this.setColorPickedHandler, this);
    },
    backgroundPickerEvents: function(method){
        this.pickerArea[method]('mousemove', this.getBackgroundPickingHandler, this);
        this.pickerArea[method]('mousedown', this.setBackgroundPickedHandler, this);
    },
    setColorPickedHandler:function() {
        if (this.overColor) {
            this.setColor(this.overColor.getHexColor());
            this.fireEvent("select",this,this.overColor.getHexColor(),this.overColor);
            this.overColor = null;
        }
        this.colorPickerBtn.removeClass("x-cpm-onpick-button");
        this.pickerArea.removeClass("x-cpm-over-cursor");

        (function(){
            Ext.ux.color.onPicking = false;
        }.createDelegate(this)).defer(500);
        
        this.colorPickerEvents("un");
        
    },
    getColorPickingHandler:function(e,t,o) {
        Ext.ux.color.onPicking = true;
        this.overColor = new Ext.ux.color.color(t.style.color || "#000000");
        this.updatePreviewValue(this.overColor.getHexColor());
    },
    setBackgroundPickedHandler:function() {
        if (this.imgContext) {
            this.imgContext.restore()
            this.imgCanvas.remove();
            delete this.imageEl;
            delete this.imgCanvas;
            delete this.imgContext;
        }
        
        if (this.overColor) {
            this.setColor(this.overColor.getHexColor());
            this.fireEvent("select",this,this.overColor.getHexColor(),this.overColor);
            this.overColor = null;
        }
        this.backgroundPickerBtn.removeClass("x-cpm-onpick-button");
        this.pickerArea.removeClass("x-cpm-over-cursor");

        (function(){ // if color choosing on trigger button, will be deferred update
            Ext.ux.color.onPicking = false;
        }.createDelegate(this)).defer(500);
        
        this.backgroundPickerEvents("un");
    },
    getBackgroundPickingHandler:function(e,t,o) {
        Ext.ux.color.onPicking = true;
        var dh = Ext.DomHelper;
        
        function getOffset( el ) {
                var _x = 0;
                var _y = 0;
                while( el && !isNaN( el.offsetLeft ) && !isNaN( el.offsetTop ) ) {
                    _x += el.offsetLeft /*- el.scrollLeft*/;
                    _y += el.offsetTop /*- el.scrollTop*/;
                    el = el.offsetParent;
                }
                return { top: _y, left: _x };
            }

        if (t.nodeName.toLowerCase() == "img") {
            if (this.nonSupportedBrowser) return false;
            if (this.imageEl !== t) {
               var offset = getOffset(t);
               
               var canvasConfig  = {
                    tag:"canvas",
                    width:t.clientWidth,
                    height:t.clientHeight,
                    style:String.format("position:absolute;z-index:-1;left:{0}px;top:{1}px;visibility:hidden;",0,0)
               };
               this.imgCanvas = dh.append(Ext.getBody(),canvasConfig,true);

               if (!this.imgCanvas.dom.getContext) { // IE old versions
                this.imgCanvas.remove();
                this.nonSupportedBrowser = true;
                return false;
               }
               
               this.imgContext = this.imgCanvas.dom.getContext("2d");
               this.imgContext.drawImage(t, 0, 0, t.clientWidth,t.clientHeight);
               this.imageEl = t;
            }
            if (this.imgCanvas && this.imgContext && this.imageEl) {
                var offset = getOffset(t);
                var data = this.imgContext.getImageData(e.xy[0]-offset.left, e.xy[1]-offset.top, 1, 1).data;
                this.overColor = new Ext.ux.color.color({r:data[0],g:data[1],b:data[2]});
                this.updatePreviewValue(this.overColor.getHexColor());
            }
            
        } else {

            this.overColor = new Ext.ux.color.color(t.style.backgroundColor || "#FFFFFF");
            this.updatePreviewValue(this.overColor.getHexColor());
        }
    },

    updatePreviewValue:function(value) {
        this.prevBox.setStyle("background-color",value);
        this.hexBox.dom.innerHTML = value;
    },
    updateVisuals:function() {
        this.hexBox.setVisible(this.showHexValue);
        this.prevBox.setVisible(this.showPrevBox);
        this.backgroundPickerBtn.setVisible(this.enablePick);
        this.colorPickerBtn.setVisible(this.enablePick);
    },
    onResizeHandler:function(o,w,h){

        this.paletteContent.setSize(w,h);
        this.paletteBody.setSize(w,h-this.paletteHeader.getHeight());
        var p = {
            t:this.paletteBody.getPadding("t"),
            l:this.paletteBody.getPadding("l"),
            b:this.paletteBody.getPadding("b"),
            r:this.paletteBody.getPadding("r")
        }
        
        this.palette.setSize(this.paletteBody.getWidth()-(p.l+p.r),this.paletteBody.getHeight()-(p.t+p.b));
        
        this.mixerContent.setSize(w,h);
        this.mixerBody.setSize(w,h-this.mixerFooter.getHeight());
        this.mixerPanel.setSize(this.mixerBody.getWidth(),this.mixerBody.getHeight());

    },

    /**
     * Set Mixer color value.
     * @param {Mixed} . See the {@link Ext.ux.color.color} config
     * for details on supported values.
     * @return {Object} this Picker
     */
    setColor:function(v) {
        if (!Ext.isEmpty(v)) {
            var color = new Ext.ux.color.color(v);
            this.value = color.getHexColor();
            this.mixerPanel.setColor(color.getHexColor());
            this.mixerPanel.setColorMode(this.colorMode); //for slider positions
            this.updatePreviewValue(color.getHexColor());
            return this;
        }
    },
    /**
     * Change this mixer colormode.
     * @param {String} The color mode. See the {@link Ext.ux.color.Modes}
     * for details on supported values.
     */
    setColorMode:function(mode) {
        if(!Ext.isEmpty(mode)) {
            this.colorMode = mode;
            this.mixerPanel.setColorMode(mode);
        }
    },
    /**
    * @param {color} . See the {@link Ext.ux.color.color} config
    */
    getColor:function() {
        return this.mixerPanel.getColor();
    },
    /**
    * @param {String} . Return HTML color hex value.
    */
    getHexColor:function() {
        return this.value;
    },
    setEnablePickers:function(bool) {
        this.enablePick = bool;
        this.updateVisuals();
    },
    setPrevBoxVisible:function(bool) {
        this.showPrevBox = bool;
        this.updateVisuals();
    },
    setHexBoxVisible:function(bool) {
        this.showHexValue = bool,
        this.updateVisuals();
    }
});
Ext.reg('colorpicker2', Ext.ux.color.Picker);




/**
 * @class Ext.ux.color.colorField
 * @extends Ext.form.TriggerField
 * Provides a color input field with a {@link Ext.ux.color.Palette},{@link Ext.ux.color.Mixer},{@link Ext.ux.color.Panel} or {@link Ext.ux.color.Picker}
 * dropdown colorSelector component and automatic color validation .
 * @constructor
 * Create a new colorField
 * @param {Object} config
 * @xtype colorfield
 */
Ext.ux.color.colorField = Ext.extend(Ext.form.TriggerField, {
    triggerClass:"x-cpm-field-trigger-transparent",
    triggerColorClass:"x-cpm-field-trigger-color",
    /**
     * @cfg {String} vtype A validation type name as defined in {@link Ext.form.VTypes} (defaults to hexcolor)
     */
    vtype:"hexcolor",
    /**
     * @cfg {String} set popup color component type. Possiblevalues 'Palette','Mixer','Panel','Picker' (defaults to Picker)
     */
    colorSelector:"Picker",
    //private
    allowedSelectors:['Palette','Mixer','Panel','Picker'],
    /**
     * @cfg {String} set the trigger position (defaults to right)
     */
    triggerPosition:"right",
    /**
     * @cfg {Boolean} fill selected color textfield baseline. Defaults to true
     */
    allowFillColor:true,
    /**
     * @cfg {String} set color is disabled error text.
     */
    disabledColorsText:"Disabled Color",
    
    constructor:function(config) {
        config = config || {};
        config.colorSelector = config.colorSelector ? this.allowedSelectors.contains(config.colorSelector.ucFirst()) ? config.colorSelector.ucFirst() :  "Picker" : "Picker";
        Ext.apply(this,config);
        Ext.ux.color.colorField.superclass.constructor.call(this,config);
    },
    initComponent:function() {
        Ext.ux.color.colorField.superclass.initComponent.call(this);
        
        this.addEvents(
            'select'
        );
        
    },
    //private, override orginal function
    onRender : function(ct, position){
        this.doc = Ext.isIE ? Ext.getBody() : Ext.getDoc();
        Ext.form.TriggerField.superclass.onRender.call(this, ct, position);
        
        
        this.wrap = this.el.wrap({cls: 'x-form-field-wrap x-form-field-trigger-wrap'}); 
        
        this.trigger = this.wrap.createChild(this.triggerConfig ||
                {tag: "div", cls: "x-form-trigger x-cpm-field-trigger " + this.triggerClass });
        
        this.initTrigger();
        
        if(!this.width){
            this.wrap.setWidth(this.el.getWidth()+this.trigger.getWidth());
        }
        
        this.resizeEl = this.positionEl = this.wrap;
    },
    onResize:function(w,h) {
        Ext.ux.color.colorField.superclass.onResize.call(this,w,h);
        var tw = this.getTriggerWidth();
            if (this.triggerPosition == "left") {
                this.trigger.setLeft(1);
                this.addClass("x-cpm-field-text-indent");
                this.el.setWidth(w);
            } else {
                this.trigger.setRight(0);
                this.removeClass("x-cpm-field-text-indent");
            }
    },
    initEvents: function() {
        Ext.ux.color.colorField.superclass.initEvents.call(this);
        this.keyNav = new Ext.KeyNav(this.el, {
            "down": function(e) {
                this.onTriggerClick();
            },
            scope: this,
            forceKeyDown: true
        });
    },
    //private
    onTriggerClick : function(){
        if(this.disabled || Ext.ux.color.onPicking == true){
            return;
        }
        if(this.menu == null){
            var menuCfg = {
                hideOnClick: false,
                listeners:{
                    afterrender:{
                        scope:this,
                        fn:function() {
                            this.menu.picker.setColor(this.getValue());
                        }
                    }
                }
            };
            this.menu = new Ext.ux.color.menu[this.colorSelector](Ext.applyIf(menuCfg,this.initialConfig));
        } else {
            this.menu.picker.setColor(this.getValue());
        }
        
        
        this.onFocus();
        
        this.menu.show(this.el, "tl-bl?");
        this.menuEvents('on');
    },
    
    menuEvents: function(method){
        this.menu[method]('select', this.onSelect, this);
        this.menu[method]('change', this.onChange, this);
        this.menu[method]('hide', this.onMenuHide, this);
        this.menu[method]('show', this.onFocus, this);
    },
    onChange:function(picker,hex,color) {
        this.setValue(hex);
        this.updateTriggerColor(hex);
    },
    onSelect: function(picker, hex,color){
        this.setValue(hex);
        this.updateTriggerColor(hex);
        this.fireEvent('select', this, hex);
        this.menu.hide();
    },
    updateTriggerColor:function(hex) {
        if (Ext.isEmpty(hex)) {
            this.trigger.removeClass(this.triggerColorClass);
            this.trigger.addClass(this.triggerClass);
            if (this.allowFillColor == true)  this.el.dom.style.backgroundColor = "transparent";
        } else {
            this.trigger.removeClass(this.triggerClass);
            this.trigger.addClass(this.triggerColorClass);
            this.trigger.dom.style.backgroundColor = String.format("{0}",hex);
            if (this.allowFillColor == true) this.el.dom.style.backgroundColor = hex;
        }
    },
    onMenuHide: function(){
        this.focus(false, 60);
        this.menuEvents('un');
    },
    parseColor:function(hexValue) {
        var color= new Ext.ux.color.color(hexValue);
        this.updateTriggerColor(color.getHexColor());
        return color.getHexColor();
    },
    // private
    beforeBlur : function(){
        var v = this.parseColor(this.getRawValue());
        if(v){
            this.setValue(v);
        }
    },
    onKeyUp : function(e,f,o) {
        this.parseColor(this.getRawValue());
    },
     // private
    onDestroy : function(){
        Ext.destroy(this.menu, this.keyNav);
        Ext.ux.color.colorField.superclass.onDestroy.call(this);
    },
    getErrors: function(value) {
        var errors = Ext.ux.color.colorField.superclass.getErrors.apply(this, arguments);
        value = this.parseColor(value || this.processValue(this.getRawValue()));
        
        if (this.disabledColors && Ext.isArray(this.disabledColors) && value && value.replace) {
            if (this.disabledColors.contains(value.replace("#",""),true)) {
                errors.push(this.disabledColorsText);
            }
        }
        
        return errors;
    },
    getValue:function() {
        return this.parseColor(Ext.ux.color.colorField.superclass.getValue.call(this)) || "";
    },
    validateBlur : function(){
        return !this.menu || !this.menu.isVisible();
    },
    setColorSelector:function(selector) {
        
        if (this.allowedSelectors.contains(selector.ucFirst())) {
            this.colorSelector = selector;
            this.menu = null;
        }
    }
    
});
Ext.reg('colorfield', Ext.ux.color.colorField);



Ext.ux.color.menu.Palette = Ext.extend(Ext.menu.Menu, {
    enableScrolling : false,
    hideOnClick : true,
    paletteId : null,
    width:278,
    height:188,
    initComponent : function(){

        Ext.apply(this, {
            plain: true,
            showSeparator: false,
            items: this.picker = new Ext.ux.color.Palette(Ext.applyIf({
                id: this.paletteId
            }, this.initialConfig))
        });
        
        if (this.initialConfig.halfMode == true) this.height = 100;
        
        Ext.ux.color.menu.Palette.superclass.initComponent.call(this);
        this.relayEvents(this.picker, ['select']);

        this.on('select', this.menuHide, this);
        if(this.handler){
            this.on('select', this.handler, this.scope || this);
        }
    },

    menuHide : function() {
        if(this.hideOnClick){
            this.hide(true);
        }
    }
});


Ext.ux.color.menu.Mixer = Ext.extend(Ext.menu.Menu, {
    enableScrolling : false,
    hideOnClick : true,
    mixerId : null,
    width:260,
    height:130,
    initComponent : function(){

        Ext.apply(this, {
            plain: true,
            showSeparator: false,
            items: this.picker = new Ext.ux.color.Mixer(Ext.applyIf({
                width:250,
                height:120,
                id: this.mixerId
            }, this.initialConfig))
        });

        Ext.ux.color.menu.Mixer.superclass.initComponent.call(this);
        this.relayEvents(this.picker, ['select','change']);

        this.on('select', this.menuHide, this);
        if(this.handler){
            this.on('select', this.handler, this.scope || this);
        }
    },

    menuHide : function() {
        if(this.hideOnClick){
            this.hide(true);
        }
    }
});


Ext.ux.color.menu.Panel = Ext.extend(Ext.menu.Menu, {
    enableScrolling : false,
    hideOnClick : true,
    panelId : null,
    width:289,
    height:199,
    initComponent : function(){

        Ext.apply(this, {
            plain: true,
            showSeparator: false,
            items: this.picker = new Ext.ux.color.Panel(Ext.applyIf({
                width:280,
                height:190,
                id: this.panelId
            }, this.initialConfig))
        });

        Ext.ux.color.menu.Panel.superclass.initComponent.call(this);
        this.relayEvents(this.picker, ['select','change']);

        this.on('select', this.menuHide, this);
        if(this.handler){
            this.on('select', this.handler, this.scope || this);
        }
    },

    menuHide : function() {
        if(this.hideOnClick){
            this.hide(true);
        }
    }
});


Ext.ux.color.menu.Picker = Ext.extend(Ext.menu.Menu, {
    width:280,
    height:215,
    enableScrolling : false,
    hideOnClick : true,
    pickerId : null,
    initComponent : function(){

        Ext.apply(this, {
            plain: true,
            showSeparator: false,
            items: this.picker = new Ext.ux.color.Picker(Ext.applyIf({
                id: this.pickerId
            }, this.initialConfig))
        });

        Ext.ux.color.menu.Picker.superclass.initComponent.call(this);
        this.relayEvents(this.picker, ['select','change']);

        this.on('select', this.menuHide, this);
        if(this.handler){
            this.on('select', this.handler, this.scope || this);
        }
    },

    menuHide : function() {
        if(this.hideOnClick){
            this.hide(true);
        }
    }
 });


Ext.ux.color.Window = Ext.extend(Ext.Window, {
    width:320,
    height:250,
    minWidth:320,
    minHeight:240,
    layout: 'fit',
    title: 'Color Mixer',

    initComponent: function() {
        this.addEvents("select");
        
        Ext.applyIf(this, {
            items: [new Ext.ux.color.Panel({
                border:false,
                plain:true,
                listeners:{
                    select:{
                        scope:this,
                        fn:function(o,c,cd){
                            this.fireEvent("select",this,c,cd);
                            this.close();
                        }
                    }
                }
            })],
            buttons:[{
                text:"Select",
                scope:this,
                handler:function() {
                    this.fireEvent("select",this,this.getMixer().getColor().getHexColor(),this.getMixer().getColor());
                    this.close();
                }
            },{
                text:"Cancel",
                scope:this,
                handler:function() {
                    this.close();
                }
            }]
        });

        Ext.ux.color.Window.superclass.initComponent.call(this);
        this.panel = this.items.itemAt(0);
    },
    getForm:function(){
        return this.panel.getForm();
    },
    getMixer:function() {
        return this.panel.getMixer();
    },
    setValue:function(hexValue) {
        this.getMixer().setValue(hexValue);
    }
});