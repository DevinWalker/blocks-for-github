import { __ } from '@wordpress/i18n';
import {
    Button,
    CheckboxControl,
    PanelBody,
    PanelRow,
    RadioControl,
    ResponsiveWrapper,
    Spinner,
    TextControl,
} from '@wordpress/components';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { dispatch, useSelect } from '@wordpress/data';

import './editor.scss';

/**
 * Main edit component.
 *
 * @param attributes
 * @param setAttributes
 * @returns {JSX.Element}
 * @constructor
 */
export default function Edit( { attributes, setAttributes } ) {
    const {
        blockType,
        profileName,
        customTitle,
        repoUrl,
        showTags,
        showForks,
        showSubscribers,
        showOpenIssues,
        showLastUpdate,
        showBio,
        showLocation,
        showOrg,
        showWebsite,
        showTwitter,
        mediaId,
        mediaUrl,
        preview,
    } = attributes;

    // Preview image when an admin hovers over the block.
    if ( preview ) {
        return (
            <Fragment>
                <img src={bfgPreviews.profile_preview} />
            </Fragment>
        );
    }

    const [showBioChecked, setShowBio] = useState( showBio );
    const [showLocationChecked, setShowLocation] = useState( showLocation );
    const [showOrgChecked, setShowOrg] = useState( showOrg );
    const [showWebsiteChecked, setShowWebsite] = useState( showWebsite );
    const [showTwitterChecked, setShowTwitter] = useState( showTwitter );

    const removeMedia = () => {
        setAttributes( {
            mediaId: 0,
            mediaUrl: ''
        } );
    };

    const onSelectMedia = ( media ) => {
        setAttributes( {
            mediaId: media.id,
            mediaUrl: media.url
        } );
    };

    const media = useSelect( ( select ) => {
        return select( 'core' ).getMedia( mediaId );
    }, [onSelectMedia] );

    return (
        <Fragment>
            <Fragment>
                <InspectorControls>
                    <PanelBody title={__( 'Block Display', 'blocks-for-github' )} initialOpen={true}>
                        <PanelRow>
                            <RadioControl
                                label={__( 'Display Options', 'blocks-for-github' )}
                                help={__( 'This option adjusts the content displayed in the block.', 'blocks-for-github' )}
                                selected={blockType}
                                options={[
                                    { label: 'Repository', value: 'repository' },
                                    { label: 'Profile', value: 'profile' },
                                ]}
                                onChange={( newBlockType ) => {
                                    setAttributes( { blockType: newBlockType } );
                                }}
                            />
                        </PanelRow>
                    </PanelBody>
                    {blockType === 'repository' && (
                        <PanelBody title={__( 'Repository Settings', 'blocks-for-github' )} initialOpen={false}>
                            <PanelRow>
                                <TextControl
                                    label={__( 'Repo URL', 'blocks-for-github' )}
                                    value={repoUrl}
                                    help={__( 'Please enter the URL contents after the `https://github.com/` string. For example: `impress-org/givewp`.', 'blocks-for-github' )}
                                    onChange={( newRepoUrl ) => {
                                        setAttributes( { repoUrl: newRepoUrl } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <TextControl
                                    label={__( 'Customize Repo Name', 'blocks-for-github' )}
                                    value={customTitle}
                                    help={__( 'Enter text to customize the title. Leave blank to use the default repo name.', 'blocks-for-github' )}
                                    onChange={( newCustomTitle ) => {
                                        setAttributes( { customTitle: newCustomTitle } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <CheckboxControl
                                    label={__( 'Show tags', 'blocks-for-github' )}
                                    checked={showTags}
                                    onChange={( newShowTags ) => {
                                        setAttributes( { showTags: newShowTags } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <CheckboxControl
                                    label={__( 'Show forks', 'blocks-for-github' )}
                                    checked={showForks}
                                    onChange={( newShowForks ) => {
                                        setAttributes( { showForks: newShowForks } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <CheckboxControl
                                    label={__( 'Show subscribers', 'blocks-for-github' )}
                                    checked={showSubscribers}
                                    onChange={( newShowSubsribers ) => {
                                        setAttributes( { showSubscribers: newShowSubsribers } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <CheckboxControl
                                    label={__( 'Show open issues', 'blocks-for-github' )}
                                    checked={showOpenIssues}
                                    onChange={( newShowOpenIssues ) => {
                                        setAttributes( { showOpenIssues: newShowOpenIssues } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <CheckboxControl
                                    label={__( 'Show last update', 'blocks-for-github' )}
                                    checked={showLastUpdate}
                                    onChange={( newShowLastUpdate ) => {
                                        setAttributes( { showLastUpdate: newShowLastUpdate } );
                                    }}
                                />
                            </PanelRow>
                        </PanelBody>
                    )}
                    {blockType === 'profile' && (
                        <PanelBody title={__( 'Profile Settings', 'blocks-for-github' )} initialOpen={false}>
                            <PanelRow>
                                <TextControl
                                    label={__( 'GitHub Username', 'blocks-for-github' )}
                                    value={profileName}
                                    help={__( 'Please enter a GitHub profile username to display for this block.', 'blocks-for-github' )}
                                    onChange={( newProfileName ) => {
                                        setAttributes( { profileName: newProfileName } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <div className="bfg-media-uploader">
                                    <p className={'bfg-label'}>
                                        <label>{__( 'Header background image', 'blocks-for-github' )}</label>
                                    </p>
                                    <MediaUploadCheck>
                                        <MediaUpload
                                            onSelect={onSelectMedia}
                                            value={attributes.mediaId}
                                            allowedTypes={['image']}
                                            render={( { open } ) => (
                                                <Button
                                                    className={attributes.mediaId === 0 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview'}
                                                    onClick={open}
                                                >
                                                    {attributes.mediaId === 0 && __( 'Choose an image', 'blocks-for-github' )}
                                                    {media !== undefined &&
                                                        <ResponsiveWrapper
                                                            naturalWidth={media.media_details.width}
                                                            naturalHeight={media.media_details.height}
                                                        >
                                                            <img src={media.source_url} />
                                                        </ResponsiveWrapper>
                                                    }
                                                </Button>
                                            )}
                                        />
                                    </MediaUploadCheck>
                                    <div className="bfg-media-btns">
                                        {attributes.mediaId !== 0 &&
                                            <MediaUploadCheck>
                                                <MediaUpload
                                                    title={__( 'Replace image', 'blocks-for-github' )}
                                                    value={attributes.mediaId}
                                                    onSelect={onSelectMedia}
                                                    allowedTypes={['image']}
                                                    render={( { open } ) => (
                                                        <Button onClick={open} isSmall variant="secondary" className={'bfg-replace-image-btn'}>{__( 'Replace image', 'blocks-for-github' )}</Button>
                                                    )}
                                                />
                                            </MediaUploadCheck>
                                        }
                                        {attributes.mediaId !== 0 &&
                                            <MediaUploadCheck>
                                                <Button onClick={removeMedia} isSmall variant="secondary">{__( 'Remove image', 'blocks-for-github' )}</Button>
                                            </MediaUploadCheck>
                                        }
                                    </div>
                                    <p className={'bfg-help-text'}>{__( 'Upload or select an image for the header background.', 'blocks-for-github' )}</p>
                                </div>
                            </PanelRow>
                            <PanelRow>
                                <TextControl
                                    label={__( 'Customize Profile Name', 'blocks-for-github' )}
                                    value={customTitle}
                                    help={__( 'Enter text to customize the title. Leave blank to use the default profile name.', 'blocks-for-github' )}
                                    onChange={( newCustomTitle ) => {
                                        setAttributes( { customTitle: newCustomTitle } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <CheckboxControl
                                    label={__( 'Show bio', 'blocks-for-github' )}
                                    checked={showBioChecked}
                                    onChange={( newShowBio ) => {
                                        setShowBio( newShowBio );
                                        setAttributes( { showBio: newShowBio } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <CheckboxControl
                                    label={__( 'Show location', 'blocks-for-github' )}
                                    checked={showLocationChecked}
                                    onChange={( newShowLocation ) => {
                                        setShowLocation( newShowLocation );
                                        setAttributes( { showLocation: newShowLocation } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <CheckboxControl
                                    label={__( 'Show organization', 'blocks-for-github' )}
                                    checked={showOrgChecked}
                                    onChange={( newShowOrg ) => {
                                        setShowOrg( newShowOrg );
                                        setAttributes( { showOrg: newShowOrg } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <CheckboxControl
                                    label={__( 'Show website', 'blocks-for-github' )}
                                    checked={showWebsiteChecked}
                                    onChange={( newShowWebsite ) => {
                                        setShowWebsite( newShowWebsite );
                                        setAttributes( { showWebsite: newShowWebsite } );
                                    }}
                                />
                            </PanelRow>
                            <PanelRow>
                                <CheckboxControl
                                    label={__( 'Show Twitter', 'blocks-for-github' )}
                                    checked={showTwitterChecked}
                                    onChange={( newShowTwitter ) => {
                                        setShowTwitter( newShowTwitter );
                                        setAttributes( { showTwitter: newShowTwitter } );
                                    }}
                                />
                            </PanelRow>
                        </PanelBody>
                    )}
                </InspectorControls>
            </Fragment>
            <Fragment>
                <div {...useBlockProps()}>
                        <ServerSideRender
                            block="blocks-for-github/block"
                            attributes={{
                                blockType: attributes.blockType,
                                customTitle: attributes.customTitle,
                                profileName: attributes.profileName,
                                repoUrl: attributes.repoUrl,
                                mediaId: attributes.mediaId,
                                mediaUrl: attributes.mediaUrl,
                                showTags: attributes.showTags,
                                showForks: attributes.showForks,
                                showSubscribers: attributes.showSubscribers,
                                showOpenIssues: attributes.showOpenIssues,
                                showLastUpdate: attributes.showLastUpdate,
                                showBio: attributes.showBio,
                                showLocation: attributes.showLocation,
                                showOrg: attributes.showOrg,
                                showWebsite: attributes.showWebsite,
                                showTwitter: attributes.showTwitter,
                            }}
                        />
                </div>
            </Fragment>
        </Fragment>
    );

}
