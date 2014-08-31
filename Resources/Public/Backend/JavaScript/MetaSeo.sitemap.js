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

Ext.ns('MetaSeo.sitemap');

Ext.onReady(function(){
    Ext.QuickTips.init();
    Ext.state.Manager.setProvider(new Ext.state.CookieProvider());

    MetaSeo.sitemap.grid.init();
});

MetaSeo.sitemap.grid = {

    init: function() {
        /****************************************************
         * grid storage
         ****************************************************/
        var gridDs = new Ext.data.Store({
            storeId: 'MetaSeoSitemapRecordsStore',
            autoLoad: true,
            remoteSort: true,
            url: MetaSeo.sitemap.conf.ajaxController + '&cmd=getList',
            reader: new Ext.data.JsonReader({
                    totalProperty: 'results',
                    root: 'rows'
                },[
                    {name: 'uid', type: 'int'},
                    {name: 'page_rootpid', type: 'int'},
                    {name: 'page_uid', type: 'int'},
                    {name: 'page_url', type: 'string' },
                    {name: 'page_depth', type: 'int' },
                    {name: 'page_language', type: 'int' },
                    {name: 'page_change_frequency', type: 'int' },
                    {name: 'page_type', type: 'int' },
                    {name: 'tstamp', type: 'string' },
                    {name: 'crdate', type: 'string' },
                    {name: 'is_blacklisted', type: 'bool' }
                ]
            ),
            sortInfo: {
                field	 : 'uid',
                direction: 'DESC'
            },
            baseParams: {
                pid						: Ext.encode( MetaSeo.sitemap.conf.pid ),
                pagerStart				: 0,
                pagingSize				: Ext.encode( MetaSeo.sitemap.conf.pagingSize ),
                sort					: MetaSeo.sitemap.conf.sortField,
                dir						: MetaSeo.sitemap.conf.sortDir,
                criteriaFulltext		: Ext.encode( MetaSeo.sitemap.conf.criteriaFulltext ),
                criteriaPageUid			: Ext.encode( MetaSeo.sitemap.conf.criteriaPageUid ),
                criteriaPageLanguage	: Ext.encode( MetaSeo.sitemap.conf.criteriaPageLanguage ),
                criteriaPageDepth		: Ext.encode( MetaSeo.sitemap.conf.criteriaPageDepth ),
                criteriaIsBlacklisted	: Ext.encode( MetaSeo.sitemap.conf.criteriaIsBlacklisted ),
                sessionToken			: Ext.encode( MetaSeo.sitemap.conf.sessionToken )
            },
            listeners: {
                beforeload: function() {
                    this.baseParams.pagingSize				= Ext.encode( MetaSeo.sitemap.conf.pagingSize );
                    this.baseParams.criteriaFulltext		= Ext.encode( MetaSeo.sitemap.conf.criteriaFulltext );
                    this.baseParams.criteriaPageUid			= Ext.encode( MetaSeo.sitemap.conf.criteriaPageUid );
                    this.baseParams.criteriaPageLanguage	= Ext.encode( MetaSeo.sitemap.conf.criteriaPageLanguage );
                    this.baseParams.criteriaPageDepth		= Ext.encode( MetaSeo.sitemap.conf.criteriaPageDepth );
                    this.baseParams.criteriaIsBlacklisted	= Ext.encode( MetaSeo.sitemap.conf.criteriaIsBlacklisted );
                    this.removeAll();
                }
            }
        });

        var function_filter = function(ob) {
            filterAction(ob, 'getItems');
        };

        var filterAction = function(ob, cmd) {
            MetaSeo.sitemap.conf.criteriaFulltext			= Ext.getCmp('searchFulltext').getValue();
            MetaSeo.sitemap.conf.criteriaPageUid			= Ext.getCmp('searchPageUid').getValue();
            MetaSeo.sitemap.conf.criteriaPageLanguage		= Ext.getCmp('searchPageLanguage').getValue();
            MetaSeo.sitemap.conf.criteriaPageDepth		= Ext.getCmp('searchPageDepth').getValue();
            if( Ext.getCmp('searchIsBlacklisted').checked == true ) {
                MetaSeo.sitemap.conf.criteriaIsBlacklisted = 1;
            } else {
                MetaSeo.sitemap.conf.criteriaIsBlacklisted = 0;
            }

            gridDs.reload();
        };

        var function_blacklist = function(ob) {
            rowAction(ob, "blacklist", MetaSeo.sitemap.conf.lang.messageBlacklistTitle, MetaSeo.sitemap.conf.lang.messageBlacklistQuestion )
        }

        var function_whitelist = function(ob) {
            rowAction(ob, "whitelist", MetaSeo.sitemap.conf.lang.messageWhitelistTitle, MetaSeo.sitemap.conf.lang.messageWhitelistQuestion )
        }

        var function_delete = function(ob) {
            rowAction(ob, "delete", MetaSeo.sitemap.conf.lang.messageDeleteTitle, MetaSeo.sitemap.conf.lang.messageDeleteQuestion )
        }

        var function_delete_all = function(ob) {
            var cmd = "deleteAll";

            var frmConfirm = new Ext.Window({
                xtype: 'form',
                width: 200,
                height: 'auto',
                modal: true,
                title: MetaSeo.sitemap.conf.lang.messageDeleteAllTitle,
                items: [
                    {
                        xtype: 'label',
                        text: MetaSeo.sitemap.conf.lang.messageDeleteQuestion
                    }
                ],
                buttons: [
                    {
                        text: MetaSeo.sitemap.conf.lang.buttonYes,
                        handler: function(cmp, e) {
                            Ext.Ajax.request({
                                url: MetaSeo.sitemap.conf.ajaxController + '&cmd=' + cmd,
                                callback: function(options, success, response) {
                                    if (response.responseText === 'true') {
                                        // reload the records and the table selector
                                        gridDs.reload();
                                    } else {
                                        alert('ERROR: ' + response.responseText);
                                    }
                                },
                                params: {
                                    'pid'			: MetaSeo.sitemap.conf.pid,
                                    sessionToken	: Ext.encode( MetaSeo.sitemap.conf.sessionToken )
                                }
                            });

                            frmConfirm.destroy();
                        }
                    },{
                        text: MetaSeo.sitemap.conf.lang.buttonNo,
                        handler: function(cmp, e) {
                            frmConfirm.destroy();
                        }
                    }
                ]
            });
            frmConfirm.show();
        }


        var rowAction = function(ob, cmd, confirmTitle, confirmText) {
            var recList = grid.getSelectionModel().getSelections();

            if( recList.length >= 1 ) {
                var uidList = [];
                for (i = 0; i < recList.length; i++) {
                    uidList.push( recList[i].json.uid );
                }
                var frmConfirm = new Ext.Window({
                    xtype: 'form',
                    width: 200,
                    height: 'auto',
                    modal: true,
                    title: confirmTitle,
                    items: [
                        {
                            xtype: 'label',
                            text: confirmText
                        }
                    ],
                    buttons: [
                        {
                            text: MetaSeo.sitemap.conf.lang.buttonYes,
                            handler: function(cmp, e) {
                                Ext.Ajax.request({
                                    url: MetaSeo.sitemap.conf.ajaxController + '&cmd=' + cmd,
                                    callback: function(options, success, response) {
                                        if (response.responseText === 'true') {
                                            // reload the records and the table selector
                                            gridDs.reload();
                                        } else {
                                            alert('ERROR: ' + response.responseText);
                                        }
                                    },
                                    params: {
                                        'uidList'		: Ext.encode(uidList),
                                        'pid'			: MetaSeo.sitemap.conf.pid,
                                        sessionToken	: Ext.encode( MetaSeo.sitemap.conf.sessionToken )
                                    }
                                });

                                frmConfirm.destroy();
                            }
                        },{
                            text: MetaSeo.sitemap.conf.lang.buttonNo,
                            handler: function(cmp, e) {
                                frmConfirm.destroy();
                            }
                        }
                    ]
                });
                frmConfirm.show();

            } else {
                // no row selected
                Ext.MessageBox.show({
                    title: confirmTitle,
                    msg: MetaSeo.sitemap.conf.lang.errorNoSelectedItemsBody,
                    buttons: Ext.MessageBox.OK,
                    minWidth: 300,
                    minHeight: 200,
                    icon: Ext.MessageBox.INFO
                });
            }
        }

        /****************************************************
         * row checkbox
         ****************************************************/
        var sm = new Ext.grid.CheckboxSelectionModel({
            singleSelect: false
        });


        /****************************************************
         * Renderer
         ****************************************************/
        var dateToday		= new Date().format("Y-m-d");
        var dateYesterday	= new Date().add(Date.DAY, -1).format("Y-m-d");

        var rendererDatetime = function(value, metaData, record, rowIndex, colIndex, store) {
            var ret = Ext.util.Format.htmlEncode(value);
            var qtip = Ext.util.Format.htmlEncode(value);

            ret = ret.split(dateToday).join('<strong>'+MetaSeo.sitemap.conf.lang.today+'</strong>');
            ret = ret.split(dateYesterday).join('<strong>'+MetaSeo.sitemap.conf.lang.yesterday+'</strong>');

            return '<div ext:qtip="' + qtip +'">' + ret + '</div>';
        }


        var rendererLanguage = function(value, metaData, record, rowIndex, colIndex, store) {
            var ret = '';
            var qtip = '';

            if( MetaSeo.sitemap.conf.languageFullList[value] ) {
                var lang = MetaSeo.sitemap.conf.languageFullList[value];

                // Flag (if available)
                if( lang.flag ) {
                    ret += '<span class="t3-icon t3-icon-flags t3-icon-flags-'+Ext.util.Format.htmlEncode(lang.flag)+' t3-icon-'+Ext.util.Format.htmlEncode(lang.flag)+'"></span>';
                    ret += '&nbsp;';
                }

                // Label
                ret += Ext.util.Format.htmlEncode(lang.label);
                qtip = Ext.util.Format.htmlEncode(lang.label);

            } else {
                ret = value;
            }

            return '<div ext:qtip="' + qtip +'">' + ret + '</div>';
        }

        var rendererType = function(value, metaData, record, rowIndex, colIndex, store) {
            var ret = '';

            if( MetaSeo.sitemap.conf.lang.sitemapPageType && MetaSeo.sitemap.conf.lang.sitemapPageType[value] ) {
                ret = Ext.util.Format.htmlEncode( MetaSeo.sitemap.conf.lang.sitemapPageType[value] );
            } else {
                ret = '<i>[' + Ext.util.Format.htmlEncode(value) + ']</i>';
            }

            return ret;
        }

        var rendererUrl = function(value, metaData, record, rowIndex, colIndex, store) {
            value = Ext.util.Format.htmlEncode(value);

            var qtip = Ext.util.Format.htmlEncode(value);

            return '<div ext:qtip="' + qtip +'">' + value + '</div>';
        }

        var rendererBoolean = function(value, metaData, record, rowIndex, colIndex, store) {
            var ret;

            if( value ) {
                ret = "<b>"+MetaSeo.sitemap.conf.lang.labelYes+"</b>";
            } else {
                ret = MetaSeo.sitemap.conf.lang.labelNo;
            }

            return ret;
        }

        /****************************************************
         * grid panel
         ****************************************************/
        var grid = new Ext.grid.GridPanel({
            layout: 'fit',
            renderTo: MetaSeo.sitemap.conf.renderTo,
            store: gridDs,
            loadMask: true,
            plugins: [new Ext.ux.plugin.FitToParent()],
            columns: [
                sm,
                {
                    id       : 'page_uid',
                    header   : MetaSeo.sitemap.conf.lang.sitemap_page_uid,
                    width    : 10,
                    sortable : true,
                    dataIndex: 'page_uid',
                    css      : 'text-align: right;'
                },
                {
                    id       : 'page_url',
                    header   : MetaSeo.sitemap.conf.lang.sitemap_page_url,
                    width    : 'auto',
                    sortable : true,
                    dataIndex: 'page_url',
                    renderer : rendererUrl
                },{
                    id       : 'page_type',
                    header   : MetaSeo.sitemap.conf.lang.sitemap_page_type,
                    width    : 10,
                    dataIndex: 'page_type',
                    renderer : rendererType
                },{
                    id       : 'page_depth',
                    header   : MetaSeo.sitemap.conf.lang.sitemap_page_depth,
                    width    : 10,
                    sortable : true,
                    dataIndex: 'page_depth',
                    css      : 'text-align: right;padding-right: 10px;'
                },{
                    id       : 'page_rendererLanguage',
                    header   : MetaSeo.sitemap.conf.lang.sitemap_page_language,
                    width    : 15,
                    sortable : true,
                    dataIndex: 'page_language',
                    renderer : rendererLanguage
                },{
                    id       : 'is_blacklisted',
                    header   : MetaSeo.sitemap.conf.lang.sitemap_page_is_blacklisted,
                    width    : 10,
                    sortable : true,
                    dataIndex: 'is_blacklisted',
                    renderer : rendererBoolean
                },{
                    id       : 'crdate',
                    header   : MetaSeo.sitemap.conf.lang.sitemap_crdate,
                    width    : 25,
                    sortable : true,
                    dataIndex: 'crdate',
                    hidden   : true,
                    renderer : rendererDatetime
                },{
                    id       : 'tstamp',
                    header   : MetaSeo.sitemap.conf.lang.sitemap_tstamp,
                    width    : 25,
                    sortable : true,
                    dataIndex: 'tstamp',
                    hidden   : true,
                    renderer : rendererDatetime
                }
            ],
            selModel: sm,
            stripeRows: true,
            autoExpandColumn: 'page_url',
            height: 350,
            width: '98%',
            frame: true,
            border: true,
            title: MetaSeo.sitemap.conf.lang.title,
            viewConfig: {
                forceFit: true,
                listeners: {
                    refresh: function(view) {
                        if (!Ext.isEmpty(MetaSeo.sitemap.conf.criteriaFulltext)) {
                            view.el.select('.x-grid3-body .x-grid3-cell').each(function(el) {
                                MetaSeo.highlightText(el.dom, MetaSeo.sitemap.conf.criteriaFulltext);
                            });
                        }
                    }
                }
            },
            tbar: [
                MetaSeo.sitemap.conf.lang.labelSearchFulltext,
                {
                    xtype: 'textfield',
                    id: 'searchFulltext',
                    fieldLabel: MetaSeo.sitemap.conf.lang.labelSearchFulltext,
                    emptyText : MetaSeo.sitemap.conf.lang.emptySearchFulltext,
                    listeners: {
                        specialkey: function(f,e){
                            if (e.getKey() == e.ENTER) {
                                function_filter(this);
                            }
                        }
                    }
                },
                {xtype: 'tbspacer', width: 10},
                MetaSeo.sitemap.conf.lang.labelSearchPageUid,
                {
                    xtype: 'numberfield',
                    id: 'searchPageUid',
                    fieldLabel: MetaSeo.sitemap.conf.lang.labelSearchPageUid,
                    emptyText : MetaSeo.sitemap.conf.lang.emptySearchPageUid,
                    width: 50,
                    listeners: {
                        specialkey: function(f,e){
                            if (e.getKey() == e.ENTER) {
                                function_filter(this);
                            }
                        }
                    }
                },
                {xtype: 'tbspacer', width: 10},
                MetaSeo.sitemap.conf.lang.labelSearchPageLanguage,
                {
                    xtype: 'combo',
                    id: 'searchPageLanguage',
                    fieldLabel: MetaSeo.sitemap.conf.lang.labelSearchPageLanguage,
                    emptyText : MetaSeo.sitemap.conf.lang.emptySearchPageLanguage,
                    listeners: {
                        select: function(f,e){
                            function_filter(this);
                        }
                    },
                    forceSelection: true,
                    editable: false,
                    mode: 'local',
                    triggerAction: 'all',
                    store: new Ext.data.ArrayStore({
                        id: 0,
                        fields: [
                            'id',
                            'label',
                            'flag'
                        ],
                        data: MetaSeo.sitemap.conf.dataLanguage
                    }),
                    valueField: 'id',
                    displayField: 'label',
                    tpl: '<tpl for="."><div class="x-combo-list-item">{flag}{label}</div></tpl>'
                },
                {xtype: 'tbspacer', width: 10},
                MetaSeo.sitemap.conf.lang.labelSearchPageDepth,
                {
                    xtype: 'combo',
                    id: 'searchPageDepth',
                    fieldLabel: MetaSeo.sitemap.conf.lang.labelSearchPageDepth,
                    emptyText : MetaSeo.sitemap.conf.lang.emptySearchPageDepth,
                    listeners: {
                        select: function(f,e){
                            function_filter(this);
                        }
                    },
                    forceSelection: true,
                    editable: false,
                    mode: 'local',
                    triggerAction: 'all',
                    store: new Ext.data.ArrayStore({
                        id: 0,
                        fields: [
                            'id',
                            'label'
                        ],
                        data: MetaSeo.sitemap.conf.dataDepth
                    }),
                    valueField: 'id',
                    displayField: 'label'
                },
                {xtype: 'tbspacer', width: 10},
                MetaSeo.sitemap.conf.lang.labelSearchIsBlacklisted,
                {
                    xtype: 'checkbox',
                    id: 'searchIsBlacklisted',
                    listeners: {
                        check: function(f,e){
                            function_filter(this);
                        }
                    }
                },
                {xtype: 'tbspacer', width: 10},
                {
                    xtype: 'button',
                    id: 'filterButton',
                    text: MetaSeo.sitemap.conf.filterIcon,
                    handler: function_filter
                }
            ],
            bbar: [
                {
                    id: 'recordPaging',
                    xtype: 'paging',
                    store: gridDs,
                    pageSize: MetaSeo.sitemap.conf.pagingSize,
                    displayInfo: true,
                    displayMsg: MetaSeo.sitemap.conf.lang.pagingMessage,
                    emptyMsg: MetaSeo.sitemap.conf.lang.pagingEmpty
                }, '->', {
                    /****************************************************
                     * Blacklist button
                     ****************************************************/

                    xtype: 'splitbutton',
                    width: 80,
                    id: 'blacklistButton',
                    text: MetaSeo.sitemap.conf.lang.buttonBlacklist,
                    title: MetaSeo.sitemap.conf.lang.buttonBlacklistHint,
                    cls: 'x-btn-over',
                    handleMouseEvents: false,
                    handler: function_blacklist,
                    menu: new Ext.menu.Menu({
                        items: [
                            // these items will render as dropdown menu items when the arrow is clicked:
                            {text: MetaSeo.sitemap.conf.lang.buttonWhitelist, handler: function_whitelist},
                        ]
                    })
                }, {
                    /****************************************************
                     * Delete button
                     ****************************************************/

                    xtype: 'splitbutton',
                    width: 80,
                    id: 'deleteButton',
                    text: MetaSeo.sitemap.conf.lang.buttonDelete,
                    title: MetaSeo.sitemap.conf.lang.buttonDeleteHint,
                    iconCls: 'delete',
                    cls: 'x-btn-over',
                    handleMouseEvents: false,
                    handler: function_delete,
                    menu: new Ext.menu.Menu({
                        items: [
                            // these items will render as dropdown menu items when the arrow is clicked:
                            {text: MetaSeo.sitemap.conf.lang.buttonDeleteAll, handler: function_delete_all},
                        ]
                    })
                }
            ]
        });

    }

};