import { __ } from '@wordpress/i18n';
import {
  PanelBody,
  PanelRow,
  TextControl,
  Button,
  Spinner,
  CheckboxControl,
  ResponsiveWrapper,
} from '@wordpress/components';
import { Fragment, useState, useEffect } from '@wordpress/element';
import { InspectorControls, MediaUpload, useBlockProps, MediaUploadCheck } from '@wordpress/block-editor';
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
    apiKeyState,
    profileName,
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

  // const [apiKeyState, setApiKeyState] = useState( '' );
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
      setAttributes( { apiKeyState: apiKeyState } );
    }
  }, [siteSettings] );

  const testApiKey = () => {
    setApiKeyLoading( true );

    // Check the API key entered.
    fetch.get( 'https://api.github.com/user', {
      headers: {
        Authorization: 'Bearer ' + apiKeyState
      }
    } ).then( ( response ) => {
      // Admin entered a good token 👍.
      // Save it and show a notice.
      dispatch( 'core' ).saveEntityRecord( 'root', 'site', {
        blocks_for_github_plugin_personal_token: apiKeyState,
      } )
        .then(
          ( { blocks_for_github_plugin_personal_token: apiKeyState } ) => {
            dispatch( 'core/notices' ).createErrorNotice( __( '🎉 Success! You have connected to the GitHub API.', 'blocks-for-github' ), {
              isDismissible: true,
              type: 'snackbar',
            } );
            setAttributes( { apiKeyState: apiKeyState } );
            setApiKeyLoading( false );
          }
        )
        .catch( ( error ) => {
          dispatch( 'core/notices' ).createErrorNotice( error.message, {
            isDismissible: true,
            type: 'snackbar',
          } );
          setAttributes( { apiKeyState: null } );
          setApiKeyLoading( false );
        } );

    } )
      .catch( ( error ) => {
        // Nice error message format (very explanatory 📢).
        const errorMessage = `${__( '🙈️ GitHub API Error:', 'blocks-for-github' )} ${error.message} ${__( 'Error Code:', 'blocks-for-github' )} ${error.code}`;
        // Delete entered API key. 🙅
        dispatch( 'core' ).saveEntityRecord( 'root', 'site', {
          blocks_for_github_plugin_personal_token: null,
        } );
        // Show nice little toast notice if error occurs. 🥂
        dispatch( 'core/notices' ).createErrorNotice( errorMessage, {
          isDismissible: true,
          type: 'snackbar',
        } );
        setApiKeyLoading( false );
      } );
  };

  return (
    <Fragment>
      <Fragment>
        <InspectorControls>
          <PanelBody title={__( 'Profile Settings', 'blocks-for-github' )} initialOpen={true}>
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
          <PanelBody title={__( 'GitHub API Setting', 'blocks-for-github' )} initialOpen={false}>
            <PanelRow>
              <TextControl
                label={__( 'GitHub personal access token', 'blocks-for-github' )}
                value={apiKeyState}
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
                  setAttributes( { apiKeyState: newApiKey } );
                }}
              />
            </PanelRow>
            <PanelRow className={'blocks-for-github-button-row'}>
              <Button
                isSecondary
                isBusy={apiKeyLoading}
                onClick={() => testApiKey( apiKeyState )}
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
          <ServerSideRender
            block="blocks-for-github/profile"
            attributes={{
              apiKeyState: attributes.apiKeyState,
              profileName: attributes.profileName,
              mediaId: attributes.mediaId,
              mediaUrl: attributes.mediaUrl,
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
