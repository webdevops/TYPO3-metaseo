/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Markus Blaschke <typo3@markus-blaschke.de> (metaseo)
 *  (c) 2013 Markus Blaschke (TEQneers GmbH & Co. KG) <blaschke@teqneers.de> (tq_seo)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
Ext.ns('MetaSeo');

MetaSeo.metaeditor = Ext.extend(Ext.Window, {
    layout: 'fit',
    width:  '90%',
    height: '90%',
    modal:  true,

    t3PageTitle: '',
    pid: 0,

    initComponent : function() {
        var window = this;

        this.title = MetaSeo.overview.conf.lang.metaeditor_title;

        if( this.t3PageTitle ) {
            this.title += ' "'+this.t3PageTitle+'"';
        }

        if( this.pid ) {
            this.title += ' [PID:'+this.pid+']';
        }

        this.items = [{
            xtype:'tabpanel',
            activeItem:0,
            //autoScroll: true,
            enableTabScroll : true,
            //autoHeight:true,
            height:340,
            //collapseMode: "mini",
            items:[
                window.initTabOpenGraph()
            ]
        }];

        this.buttons = [{
            text: MetaSeo.overview.conf.lang.button_cancel,
            handler: function(cmp, e) {
                window.onClose(false);
                window.destroy();
            }
        },{
            text: MetaSeo.overview.conf.lang.button_save,
            handler: function(cmp, e) {
                window.saveMeta(function() {
                    window.onClose(true);
                    window.destroy();
                });
            }
        }];


        // call parent
        MetaSeo.metaeditor.superclass.initComponent.call(this);

        this.addListener("show", function() {
            var el = window.getEl();
            el.mask();

            window.loadMeta(function() {
                el.unmask();
            });
        });


    },

    onClose: function(reload) {
        // placeholder
    },

    loadMeta: function(callback) {
        var me = this;


        // Process data from database/ajax call
        var callbackSuccess = function(response) {
            var responseJson =  Ext.util.JSON.decode(response.responseText);

            for( var index in responseJson ) {
                var value = responseJson[index];

                // Inject data into form
                var formField = me.find("name", index);
                if( formField.length == 1 ) {
                    formField = formField[0];
                    formField.setValue(value);
                }
            }

            // auto enable fields
            me.onChangeOgType();

            callback();
        }

        var callbackFailure = function() {
            // TODO
        }

        Ext.Ajax.request({
            url: MetaSeo.overview.conf.ajaxController + '&cmd=loadAdvMetaTags',
            params: {
                pid             : Ext.encode(me.pid),
                sysLanguage     : Ext.encode( MetaSeo.overview.conf.sysLanguage ),
                mode            : Ext.encode( MetaSeo.overview.conf.listType ),
                sessionToken    : Ext.encode( MetaSeo.overview.conf.sessionToken )
            },
            success: callbackSuccess,
            failure: callbackFailure
        });
    },

    saveMeta: function(callbackSuccess) {
        var me = this;

        var metaTagList = {};

        var formOpenGraph = this.find("name", "form-opengraph");
        if( formOpenGraph.length = 1 ) {
            formOpenGraph = formOpenGraph[0];

            formOpenGraph.items.each(function(formField) {
                    if( formField.isVisible() ) {
                    var formFieldName  = formField.getName();
                    var formFieldValue = formField.getValue();

                    metaTagList[formFieldName] = formFieldValue;
                }
            });
        }

        var callbackFailure = function() {
            // TODO: failure function
        }

        Ext.Ajax.request({
            url: MetaSeo.overview.conf.ajaxController + '&cmd=updateAdvMetaTags',
            params: {
                pid             : Ext.encode(me.pid),
                metaTags        : Ext.encode(metaTagList),
                sysLanguage     : Ext.encode( MetaSeo.overview.conf.sysLanguage ),
                mode            : Ext.encode( MetaSeo.overview.conf.listType ),
                sessionToken    : Ext.encode( MetaSeo.overview.conf.sessionToken )
            },
            success: callbackSuccess,
            failure: callbackFailure
        });
    },

    onChangeOgType: function() {
        var formOpenGraph = this.find("name", "form-opengraph")[0];
        var typeField = formOpenGraph.find("name", "og:type")[0];

        // Get current type
        var ogType           = typeField.getValue();

        // Default types
        var ogTypeDefault    = "og:general";
        var ogTypeMain       = "og:general";
        var ogTypeMainAndSub = "og:general";

        // Lookup current selected type
        var ogTypeMatch = ogType.match(/^([^:]+):?([^:]+)?/);
        if( ogTypeMatch ) {
            ogTypeMain = 'og:'+ogTypeMatch[1];

            if( ogTypeMatch[2] ) {
                ogTypeMainAndSub  = 'og:'+ogTypeMatch[1]+'-'+ogTypeMatch[2];
            }
        }

        // dynamic dis- and enable form elements
        formOpenGraph.items.each(function(formField) {
            if( formField.metaSeoFieldCat ) {
                if( MetaSeo.inList(formField.metaSeoFieldCat, ogTypeDefault)
                    || MetaSeo.inList(formField.metaSeoFieldCat, ogTypeMain)
                    || MetaSeo.inList(formField.metaSeoFieldCat, ogTypeMainAndSub) ) {
                    formField.show();
                } else {
                    formField.hide();
                }
            }
        });

    },

    initTabOpenGraph: function() {
        var me = this;

        var panel = {
            xtype: "panel",
            name: "form-opengraph",
            title: MetaSeo.overview.conf.lang.metaeditor_tab_opengraph,
            layout: {
                type: 'form'
            },
            padding: 10,
            labelWidth: 150,
            autoScroll: true,
            overflowY: 'scroll',
            items: []
        };

        var fieldWidth = 375;

        // ########################
        // OG: General fields
        // ########################
        panel.items.push(
            {
                xtype: "textfield",
                fieldLabel: 'og:title',
                name: 'og:title',
                width: fieldWidth,
                metaSeoFieldCat: 'og:general'
            },{
                xtype: 'combo',
                fieldLabel: 'og:type',
                name: 'og:type',
                listeners: {
                    select: function(f,e){
                        // dynamic field handling
                        me.onChangeOgType();
                    }
                },
                forceSelection: true,
                editable: false,
                mode: 'local',
                triggerAction: 'all',
                value : "",
                store: new Ext.data.ArrayStore({
                    id: 0,
                    fields: [
                        'id',
                        'label'
                    ],
                    data: [
                        ["", "---"],
                        ["article", "article"],
                        ["book", "book"],
                        ["profile", "profile"],
                        ["website", "website"],

                        ["music.song", "music.song"],
                        ["music.album", "music.album"],
                        ["music.playlist", "music.playlist"],
                        ["music.radio_station", "music.radio_station"],

                        ["video.movie", "video.movie"],
                        ["video.episode", "video.episode"],
                        ["video.tv_show", "video.tv_show"],
                        ["video.other", "video.other"]
                    ]
                }),
                valueField: 'id',
                displayField: 'label',
                width: fieldWidth,
                metaSeoFieldCat: 'og:general'
            }, {
                xtype: "textfield",
                fieldLabel: 'og:image',
                name: 'og:image',
                width: fieldWidth,
                metaSeoFieldCat: 'og:general'
            }, {
                xtype: "textfield",
                fieldLabel: 'og:description',
                name: 'og:description',
                width: fieldWidth,
                metaSeoFieldCat: 'og:general'
            }
        );

        // ########################
        // OG: Music General
        // ########################

        // ########################
        // OG: Music Song
        // ########################
// TODO: add array support
//        panel.items.push(
//            {
//                xtype: "textfield",
//                fieldLabel: 'og:music:duration',
//                name: 'og:music:duration',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:music:song'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:music:album',
//                name: 'og:music:duration',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:music:song'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:music:album:disc',
//                name: 'og:music:album:disc',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:music:song'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:music:album:track',
//                name: 'og:music:album:track',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:music:song'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:music:musician',
//                name: 'og:music:musician',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:music:song'
//            }
//        );


        // ########################
        // OG: Music Album
        // ########################

        // TODO

        // ########################
        // OG: Music Playlist
        // ########################

        // TODO

        // ########################
        // OG: Music Radio
        // ########################

        // TODO



        // ########################
        // OG: Video Movie/TvShow/Other
        // ########################

        // TODO

        // ########################
        // OG: Video Episode
        // ########################

        // TODO

        // ########################
        // OG: article
        // ########################
// TODO: add array support
//        panel.items.push(
//            {
//                xtype: "textfield",
//                fieldLabel: 'og:article:author',
//                name: 'og:article:author',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:article'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:article:isbn',
//                name: 'og:article:isbn',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:article'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:article:published_time',
//                name: 'og:article:published_time',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:article'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:article:modified_time',
//                name: 'og:article:modified_time',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:article'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:article:expiration_time',
//                name: 'og:article:expiration_time',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:article'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:article:section',
//                name: 'og:article:section',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:article'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:article:tag',
//                name: 'og:article:tag',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:article'
//            }
//        );

        // ########################
        // OG: book
        // ########################
// TODO: add array support
//        panel.items.push(
//            {
//                xtype: "textfield",
//                fieldLabel: 'og:book:author',
//                name: 'og:book:author',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:book'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:book:isbn',
//                name: 'og:book:isbn',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:book'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:book:release_date',
//                name: 'og:book:release_date',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:book'
//            }, {
//                xtype: "textfield",
//                fieldLabel: 'og:book:tag',
//                name: 'og:book:tag',
//                width: fieldWidth,
//                metaSeoFieldCat: 'og:book'
//            }
//        );

        // ########################
        // OG: Profile
        // ########################
        panel.items.push(
            {
                xtype: "textfield",
                fieldLabel: 'og:profile:first_name',
                name: 'og:profile:first_name',
                width: fieldWidth,
                metaSeoFieldCat: 'og:profile'
            }, {
                xtype: "textfield",
                fieldLabel: 'og:profile:last_name',
                name: 'og:profile:last_name',
                width: fieldWidth,
                metaSeoFieldCat: 'og:profile'
            }, {
                xtype: "textfield",
                fieldLabel: 'og:profile:username',
                name: 'og:profile:username',
                width: fieldWidth,
                metaSeoFieldCat: 'og:profile'
            }, {
                xtype: 'combo',
                fieldLabel: 'og:profile:gender',
                name: 'og:profile:gender',
                listeners: {
                    select: function(f,e){
                        // dynamic field handling
                        me.onChangeOgType();
                    }
                },
                forceSelection: true,
                editable: false,
                mode: 'local',
                triggerAction: 'all',
                value : "",
                store: new Ext.data.ArrayStore({
                    id: 0,
                    fields: [
                        'id',
                        'label'
                    ],
                    data: [
                        ["", "---"],
                        ["male", "male"],
                        ["female", "female"]
                    ]
                }),
                valueField: 'id',
                displayField: 'label',
                width: fieldWidth,
                metaSeoFieldCat: 'og:profile'
            }
        );


        return panel;
    }

});
