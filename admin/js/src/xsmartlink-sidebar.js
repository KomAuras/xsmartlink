const {__} = wp.i18n;

const {
    PluginSidebar,
    PluginSidebarMoreMenuItem
} = wp.editPost;

const {
    PanelBody,
    RadioControl
} = wp.components;

const {
    Component,
    Fragment
} = wp.element;

const {withSelect} = wp.data;

const {compose} = wp.compose;

const {registerPlugin} = wp.plugins;

class xsmartlink_sidebar extends Component {
    constructor() {
        super(...arguments);

        this.state = {
            key: '_xsmartlink_type',
            value: '',
        }

        wp.apiFetch({path: `/wp/v2/posts/${this.props.postId}`, method: 'GET'}).then(
            (data) => {
                result = data.meta._xsmartlink_type;
                if (result == '')
                    result = 'd';
                this.setState({
                    value: result
                });
                return data;
            },
            (err) => {
                return err;
            }
        );
    }

    static getDerivedStateFromProps(nextProps, state) {
        if ((nextProps.isPublishing || nextProps.isSaving) && !nextProps.isAutoSaving) {
            wp.apiRequest({
                path: `/xsmartlink/v1/update-meta?id=${nextProps.postId}`,
                method: 'POST',
                data: state
            }).then(
                (data) => {
                    return data;
                },
                (err) => {
                    return err;
                }
            );
        }
    }

    render() {
        return (
            <Fragment>
                <PluginSidebarMoreMenuItem target="xsmartlink-sidebar">
                    {wma.Acceptors}
                </PluginSidebarMoreMenuItem>
                <PluginSidebar name="xsmartlink-sidebar" title={wma.Acceptors}>
                    <PanelBody>
                        <RadioControl
                            label={wma.PostType}
                            selected={this.state.value}
                            options={[
                                {label: wma.Acceptor, value: 'a'},
                                {label: wma.Donor, value: 'd'},
                            ]}
                            onChange={(value) => {
                                this.setState({
                                    value
                                });
                            }}
                        />
                    </PanelBody>
                </PluginSidebar>
            </Fragment>
        )
    }
}

const HOC = withSelect((select, {forceIsSaving}) => {
    const {
        getCurrentPostId,
        isSavingPost,
        isPublishingPost,
        isAutosavingPost,
    } = select('core/editor');
    return {
        postId: getCurrentPostId(),
        isSaving: forceIsSaving || isSavingPost(),
        isAutoSaving: isAutosavingPost(),
        isPublishing: isPublishingPost(),
    };
})(xsmartlink_sidebar);

registerPlugin('xsmartlink', {
    icon: 'admin-links',
    render: HOC,
});