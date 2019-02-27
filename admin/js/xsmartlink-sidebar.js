var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var __ = wp.i18n.__;
var _wp$editPost = wp.editPost,
    PluginSidebar = _wp$editPost.PluginSidebar,
    PluginSidebarMoreMenuItem = _wp$editPost.PluginSidebarMoreMenuItem;
var _wp$components = wp.components,
    PanelBody = _wp$components.PanelBody,
    RadioControl = _wp$components.RadioControl;
var _wp$element = wp.element,
    Component = _wp$element.Component,
    Fragment = _wp$element.Fragment;
var withSelect = wp.data.withSelect;
var compose = wp.compose.compose;
var registerPlugin = wp.plugins.registerPlugin;

var xsmartlink_sidebar = function (_Component) {
    _inherits(xsmartlink_sidebar, _Component);

    function xsmartlink_sidebar() {
        _classCallCheck(this, xsmartlink_sidebar);

        var _this = _possibleConstructorReturn(this, (xsmartlink_sidebar.__proto__ || Object.getPrototypeOf(xsmartlink_sidebar)).apply(this, arguments));

        _this.state = {
            key: '_xsmartlink_type',
            value: ''
        };

        wp.apiFetch({ path: '/wp/v2/posts/' + _this.props.postId, method: 'GET' }).then(function (data) {
            result = data.meta._xsmartlink_type;
            if (result == '') result = 'd';
            _this.setState({
                value: result
            });
            return data;
        }, function (err) {
            return err;
        });
        return _this;
    }

    _createClass(xsmartlink_sidebar, [{
        key: 'render',
        value: function render() {
            var _this2 = this;

            return wp.element.createElement(
                Fragment,
                null,
                wp.element.createElement(
                    PluginSidebarMoreMenuItem,
                    { target: 'xsmartlink-sidebar' },
                    wma.Acceptors
                ),
                wp.element.createElement(
                    PluginSidebar,
                    { name: 'xsmartlink-sidebar', title: wma.Acceptors },
                    wp.element.createElement(
                        PanelBody,
                        null,
                        wp.element.createElement(RadioControl, {
                            label: wma.PostType,
                            selected: this.state.value,
                            options: [{ label: wma.Acceptor, value: 'a' }, { label: wma.Donor, value: 'd' }],
                            onChange: function onChange(value) {
                                _this2.setState({
                                    value: value
                                });
                            }
                        })
                    )
                )
            );
        }
    }], [{
        key: 'getDerivedStateFromProps',
        value: function getDerivedStateFromProps(nextProps, state) {
            if ((nextProps.isPublishing || nextProps.isSaving) && !nextProps.isAutoSaving) {
                wp.apiRequest({
                    path: '/xsmartlink/v1/update-meta?id=' + nextProps.postId,
                    method: 'POST',
                    data: state
                }).then(function (data) {
                    return data;
                }, function (err) {
                    return err;
                });
            }
        }
    }]);

    return xsmartlink_sidebar;
}(Component);

var HOC = withSelect(function (select, _ref) {
    var forceIsSaving = _ref.forceIsSaving;

    var _select = select('core/editor'),
        getCurrentPostId = _select.getCurrentPostId,
        isSavingPost = _select.isSavingPost,
        isPublishingPost = _select.isPublishingPost,
        isAutosavingPost = _select.isAutosavingPost;

    return {
        postId: getCurrentPostId(),
        isSaving: forceIsSaving || isSavingPost(),
        isAutoSaving: isAutosavingPost(),
        isPublishing: isPublishingPost()
    };
})(xsmartlink_sidebar);

registerPlugin('xsmartlink', {
    icon: 'admin-links',
    render: HOC
});
