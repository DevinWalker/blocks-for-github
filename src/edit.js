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
        apiKey,
        profileName,
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

    const [apiKeyLoading, setApiKeyLoading] = useState( false );
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

    const siteSettings = useSelect( ( select ) => {
        return select( 'core' ).getEntityRecord( 'root', 'site' );
    }, [] );

    useEffect( () => {
        if ( siteSettings ) {
            const {
                blocks_for_github_plugin_personal_token: apiKeyState,
            } = siteSettings;
            setAttributes( { apiKey: apiKeyState } );
        }
    }, [siteSettings] );

    const testApiKey = async() => {
        setApiKeyLoading( true );

        try {
            // Send a request to the GitHub API with the entered API key.
            const response = await fetch( 'https://api.github.com/user', {
                headers: {
                    Authorization: 'Bearer ' + apiKey
                }
            } );
            if ( response.ok ) {
                // If the response is successful, save the entered API key and display a success notice.
                dispatch( 'core' ).saveEntityRecord( 'root', 'site', {
                    blocks_for_github_plugin_personal_token: apiKey,
                } );
                dispatch( 'core/notices' ).createInfoNotice( __( '🎉 Success! You have connected to the GitHub API.', 'blocks-for-github' ), {
                    isDismissible: true,
                    type: 'snackbar',
                } );
                setAttributes( { apiKey: apiKey } );
            } else {
                // If the response is not successful, throw an error.
                const error = await response.json();
                throw new Error( error.message );
            }
        } catch ( error ) {
            // If there's an error, delete the entered API key and display an error notice.
            const errorMessage = `${__( '🙈️ GitHub API Error:', 'blocks-for-github' )} ${error.message} ${__( 'Error Code:', 'blocks-for-github' )} ${error.code}`;
            dispatch( 'core' ).saveEntityRecord( 'root', 'site', {
                blocks_for_github_plugin_personal_token: null,
            } );
            dispatch( 'core/notices' ).createInfoNotice( errorMessage, {
                isDismissible: true,
                type: 'snackbar',
            } );
        } finally {
            // Set the loading state to false.
            setApiKeyLoading( false );
        }
    };

    return (
        <Fragment>
            <Fragment>
                <InspectorControls>
                    <PanelBody title={__( 'Block Display', 'blocks-for-github' )} initialOpen={true}>
                        <PanelRow>
                            <RadioControl
                                label={__( 'Display Options', 'blocks-for-github' )}
                                help={__( 'This option adjusts the content displayed in the block. Some views require an API key to display.', 'blocks-for-github' )}
                                selected={blockType}
                                options={[
                                    { label: 'Repository', value: 'repository' },
                                    { label: 'Profile (API Key Required)', value: 'profile' },
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
                    <PanelBody title={__( 'GitHub Access Token', 'blocks-for-github' )} initialOpen={false}>
                        <PanelRow>
                            <TextControl
                                label={__( 'Access Token', 'blocks-for-github' )}
                                value={apiKey}
                                type={'password'}
                                help={
                                    <>
                                        {__( 'Please enter your personal access token to use this block. To access your GitHub personal access token', 'blocks-for-github'
                                        )}{' '}
                                        <a
                                            href="https://github.com/settings/tokens"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {__(
                                                'click here',
                                                'blocks-for-github'
                                            )}
                                        </a>{'.'}
                                    </>
                                }
                                onChange={( newApiKey ) => {
                                    setAttributes( { apiKey: newApiKey } );
                                }}
                            />
                        </PanelRow>
                        <PanelRow className={'blocks-for-github-button-row'}>
                            <Button
                                isSecondary
                                isBusy={apiKeyLoading}
                                onClick={() => testApiKey( apiKey )}
                            >
                                {__( 'Save API Key', 'blocks-for-github' )}
                            </Button>
                            <div className="jw-text-center">
                                {apiKeyLoading && <Spinner />}
                            </div>
                        </PanelRow>
                    </PanelBody>
                </InspectorControls>
            </Fragment>
            <Fragment>

                <div {...useBlockProps()}>
                    {(apiKeyLoading) &&
                        <div className="bfg-loading">
                            <Spinner size={'large'} />
                        </div>
                    }

                    {(apiKey === '' || apiKey === undefined) &&
                        <div id="bfg-info-wrap">
                            <div className="bfg-info-wrap-inner">
                                <span className="bfg-info-emoji">👋</span>
                                <h2>{__( 'Welcome to Blocks for GitHub!', 'blocks-for-github' )}</h2>
                                <p>{__( 'To begin, please enter your GitHub personal access token in the block\'s setting panel to the right. Don\'t worry, you\'ll only have to do this one time.', 'blocks-for-github' )}</p>
                            </div>
                        </div>
                    }

                    {(apiKey !== '' && apiKey !== undefined && !apiKeyLoading) && (
                        <ServerSideRender
                            block="blocks-for-github/block"
                            attributes={{
                                blockType: attributes.blockType,
                                apiKey: attributes.apiKey,
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
                    )}
                </div>
            </Fragment>
        </Fragment>
    );

}
