Ext.onReady(function() {
    Ext.QuickTips.init();
    
    
    //fix Ext3 createContextualFragment error
    if ((typeof Range !== "undefined") && !Range.prototype.createContextualFragment)
    {
    	Range.prototype.createContextualFragment = function(html)
    	{
    		var frag = document.createDocumentFragment(), 
    		div = document.createElement("div");
    		frag.appendChild(div);
    		div.outerHTML = html;
    		return frag;
    	};
    }  

   var colorpanel = new Ext.Panel({
        title: 'Color Fields',
        width: 400,
        height:300,
        layout: 'fit',
        // The fields
        defaultType: 'colorfield',
        items: [{
                    xtype:"form",
                    bodyStyle:"padding:10px;",
                    defaults:{
                      anchor: '100%',
                      labelWidth:150  
                    },
                    url: 'save-form.php',
                    items:[{
                        xtype:"colorfield",
                        fieldLabel:"background",
                        value:"#FF0000",
                        name:"backcolor",
                        colorSelector:"mixer"
                    },{
                        xtype:"colorfield",
                        fieldLabel:"text color",
                        name:"backcolor2",
                        colorSelector:"palette",
                        disabledColors:["ff0000","cccccc","FFFFFF","808080","909090","eeeeee"]
                    },{
                        xtype:"colorfield",
                        fieldLabel:"text color",
                        name:"backcolor3",
                        colorSelector:"panel"
                    },{
                        xtype:"colorfield",
                        fieldLabel:"text color",
                        name:"backcolor4",
                        colorSelector:"palette",
                        halfMode:true
                    },{
                        xtype:"colorfield",
                        fieldLabel:"text color",
                        name:"backcolor5",
                        triggerPosition:"left",
                        disabledColors:["ff0000","cccccc","FFFFFF"],
                        enablePick:true,
                        allowBlank:false,
                        allowFillColor:false
                    }],// Reset and Submit buttons
                    buttons: [{
                        text: 'Reset',
                        handler: function() {
                            colorpanel.items.itemAt(0).getForm().reset();
                        }
                    }, {
                        text: 'Submit',
                        formBind: true, //only enabled once the form is valid
                        disabled: true,
                        handler: function() {
                            var form = colorform.getForm();
                            if (form.isValid()) {
                                form.submit({
                                    success: function(form, action) {
                                       Ext.Msg.alert('Success', action.result.msg);
                                    },
                                    failure: function(form, action) {
                                        Ext.Msg.alert('Failed', action.result.msg);
                                    }
                                });
                            }
                        }
                    }]
                }],
        renderTo: document.getElementById("formtest")
    });
    

	
	/*
	*  EDITOR GRID TEST
	*/

   var grid =  new Ext.grid.EditorGridPanel({
        renderTo:"gridtest",
        height:200,
                    store: new Ext.data.Store({
                        autoDestroy: true,
                        url: 'humans.xml',
                        reader: new Ext.data.XmlReader({
                            record: 'human',
                            fields: [
                                {name: 'fullname', type: 'string'},
                                {name: 'hair', type: 'string'},
                                {name: 'beard', type: 'string'},
                                {name: 'eye', type: 'string'},           
                                {name: 'skin', type: 'string'}
                            ]
                        }),
                        sortInfo: {field:'fullname', direction:'ASC'}
                    }),
                    cm: new Ext.grid.ColumnModel({
                        defaults: {
                            sortable: true // columns are not sortable by default           
                        },
                        columns: [{
                            id: 'fullname',
                            header: 'Name',
                            dataIndex: 'fullname',
                            width: 220,
                            editor: new Ext.form.TextField({
                                allowBlank: false
                            })
                        }, {
                            header: 'Hair Color',
                            dataIndex: 'hair',
                            width: 90,
                            editor: new Ext.ux.color.colorField()
                        }, {
                            header: 'Beard Color',
                            dataIndex: 'beard',
                            width: 90,
                            editor: new Ext.ux.color.colorField()
                        }, {
                            header: 'Eye Color',
                            dataIndex: 'eye',
                            width: 90,
                            editor: new Ext.ux.color.colorField()
                        },{
                            header: 'Skin Color',
                            dataIndex: 'skin',
                            width: 90,
                            editor: new Ext.ux.color.colorField()
                        }]
                    }),
                    autoExpandColumn: 'fullname', // column with this id will be expanded
                    title: 'Characters',
                    frame: true,
                    clicksToEdit: 1
                })

    grid.getStore().load();
	
	
	/* 
	*WINDOW TESTS
	*/
	new Ext.Button({
        renderTo:document.getElementById("buttontest"),
        text:"color window",
        handler:function(btn) {
          var win = new Ext.ux.color.Window({
            listeners:{
                    select:{
                        scope:this,
                        fn:function(o,c,co){
                            Ext.get(Ext.getBody()).setStyle("background-color","#"+c);
                        }
                    }
                }
        });
        win.show();
        },
        scope:this
    });
});