BX.ready(function (){

    "use strict";
    BX.namespace("InputMultiSearch");
    BX.InputMultiSearch = function(options) {

        this._state = {
            dropdown: [],
            storage: [...options.storage]
        };

        this.options = {
            url: options.url ? options.url : location.href,
            inputName: options.inputName ? options.inputName : 'prop_filter',
            delay: 350
        };

        this.c_main = document.querySelector(".sw24-input-multi-search");
        this.c_storage = this.c_main.querySelector(".storage .storage_wrapper");
        this.c_search = this.c_main.querySelector(".search .search_input");
        this.c_dropdown = this.c_main.querySelector(".search_dropdown");

        this._init();
    };

    BX.InputMultiSearch.prototype = {

        _init: function () {
            this._render();
            this._onEvents();
        },

        _render: function(){
            this._handleDropDownRender();
            this._handleStorageRender();
        },

        _handleDropDownRender: function() {

            this.c_dropdown.innerHTML = "";

            if(this._state.dropdown.length > 0) {
                for (let iblock of this._state.dropdown)
                {
                    // generate dom elements property
                    let props = [];

                    for (let prop of iblock.props)
                    {
                        props.push(BX.create({
                            tag: "div",
                            attrs: {
                                className: "search_dropdown_item" + (this._isHasPropStorage(prop.id) ?  " _disabled" : ""),
                                "data-id": prop.id,
                                "data-name": prop.name,
                                "data-iblock-id": iblock.id,
                                "data-iblock-name": iblock.name
                            },
                            text: prop.name + " [" + prop.id + "]",
                            events: {
                                click: this._action.bind(
                                    this,
                                    this._handleStorageAdd
                                ),
                            }
                        }));
                    }

                    this.c_dropdown.append(BX.create({
                        tag: "div",
                        attrs: {
                            className: "search_dropdown_group"
                        },
                        children: [
                            BX.create({
                                tag: "div",
                                attrs: { className: "search_dropdown_group_name" },
                                text: iblock.name + " [" + iblock.id + "]"
                            }),
                            BX.create({
                                tag: "div",
                                attrs: { className: "search_dropdown_group_list"},
                                children: props,
                            }),
                        ]
                    }));
                }
            }
            else
            {
                this.c_dropdown.append(BX.create({
                    tag: "div",
                    attrs: {
                        className: "search_dropdown_empty"
                    },
                    text: "Empty search...."
                }));
            }
        },

        _handleStorageRender: function(){

            if(!this._state.storage) {
                return false;
            }

            this.c_storage.innerHTML = "";

            for(let item of this._state.storage)
            {
                let elementDom = BX.create({
                    tag: "div",
                    attrs: {
                        className: "storage_item",
                        "data-id": item.id,
                    },
                    children: [
                        BX.create({
                            tag: "input",
                            props: {
                                value: item.id,
                                name: this.options.inputName+"[]"
                            },
                            attrs: { type: "hidden" }
                        }),
                        BX.create({
                            tag: "div",
                            attrs: {
                                className: "item_name",
                                title: this._formatName("title", item.name, {iblockId: item.iblockId, iblockName: item.iblockName, id: item.id})
                            },
                            text: this._formatName("text", item.name, {id: item.id})
                        }),
                        BX.create({
                            tag: "div",
                            attrs: { className: "item_delete" },
                            html: '<i class="fa fa-times"></i>',
                            events: {
                                click: this._action.bind(
                                    this,
                                    this._handleStorageRemove
                                ),
                            }
                        }),
                    ]
                });

                this.c_storage.append(elementDom);
            }
        },

        _handleStorageRemove: function(e){
            let parent = BX.findParent(e.target, {class: "storage_item"});
            this._storageRemove(parent.dataset.id);
        },

        _handleStorageAdd: async function(e) {

            let storageId = e.target.dataset.id;
            let storageName = e.target.dataset.name;
            let storageIblockId = e.target.dataset.iblockId;

            if(!this._isHasPropStorage(storageId)) {
                this._storageAdd({
                    id: storageId,
                    iblockId: storageIblockId,
                    iblockName: e.target.dataset.iblockName,
                    name: storageName
                });
            } else {
                this._storageRemove(storageId);
            }
        },

        _handleInput: async function(data) {

            if(!data) return;

            let _self = this;
            return new Promise(function (resolve, reject) {

                if(_self._state.timeout){
                    clearTimeout(_self._state.timeout)
                }

                _self._state.timeout = setTimeout(() => {
                        let fields = {
                            value: data,
                            storage: _self._state.storage,
                            type: "getPropList",
                            ajax: "y"
                        };
                        _self._request(fields).then( response => resolve(_self._setDropdown(response)));
                    },
                    _self.options.delay
                );

            });
        },

        _setDropdown(response)
        {
            let list = [];
            if(typeof response === "object") {
                for (let i in response)
                {
                    list.push(response[i]);
                }
            }
            this._state.dropdown = list;
        },

        _storageAdd: function(data) {
            this._state.storage.push(data);
        },

        _storageRemove: function(itemId) {
            for (let i = 0; i < this._state.storage.length; i++)
            {
                if(this._state.storage[i].id == itemId)
                {
                    this._state.storage.splice(i,1);
                }
            }
        },

        _isHasPropStorage: function(propId){
            return this._state.storage.find(function (item) {
                if(item.id == propId)
                {
                    return true;
                }
            });
        },

        _action: async function(callback, data){
            this.c_search.classList.add("_load");

            await callback.bind(this, data)();

            this._render();
            this.c_search.classList.remove("_load");
        },

        _formatName: function(type = "text", name, params)
        {
            if(type == "title") {
                return "[" + params.iblockId + " - "+params.iblockName+"] " + name + "("+params.id+")"
            }
            else {
                if(!name || name.length == 0) {
                    return params.id
                }
                if(name.length > 10) {
                    return (name) ? name.slice(0, 10) + "... [" + params.id  + "]" : params.id
                }

                return name + " [" + params.id  + "]";
            }
        },

        _request: function(fields) {
            let _self = this;
            return new Promise(function (resolve, reject) {
                BX.ajax({
                    url: _self.options.url,
                    data: fields,
                    method: "POST",
                    onsuccess: function (response) {
                        resolve(BX.parseJSON(response))
                    }
                });

            });
        },

        _onEvents: function() {
            let _self = this;

            this.c_search.querySelector("input")
                .addEventListener("input", function () {
                    _self._action.call(_self, _self._handleInput, this.value)
                });

        }
    };

});

