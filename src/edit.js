import {__} from '@wordpress/i18n';
import {
	Button,
	CheckboxControl,
	PanelBody,
	PanelRow,
	RadioControl,
	ResponsiveWrapper,
	TextControl,
} from '@wordpress/components';
import {Fragment, useState} from '@wordpress/element';
import {InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps} from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import {useSelect} from '@wordpress/data';

import './editor.scss';

/**
 * Main edit component.
 *
 * @param  attributes.attributes
 * @param  attributes
 * @param  setAttributes
 * @param  attributes.setAttributes
 * @return {JSX.Element}
 * @class
 */
export default function Edit({attributes, setAttributes}) {
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
	if (preview) {
		return (
			<Fragment>
				<img
					src={bfgPreviews.block_preview}
					alt={__('Blocks for GitHub', 'stellarpay')}
					style={{width: '100%', height: 'auto'}}
				/>
			</Fragment>
		);
	}

	const [showBioChecked, setShowBio] = useState(showBio);
	const [showLocationChecked, setShowLocation] = useState(showLocation);
	const [showOrgChecked, setShowOrg] = useState(showOrg);
	const [showWebsiteChecked, setShowWebsite] = useState(showWebsite);
	const [showTwitterChecked, setShowTwitter] = useState(showTwitter);

	const removeMedia = () => {
		setAttributes({
			mediaId: 0,
			mediaUrl: '',
		});
	};

	const onSelectMedia = (media) => {
		setAttributes({
			mediaId: media.id,
			mediaUrl: media.url,
		});
	};

	const media = useSelect(
		(select) => {
			return select('core').getMedia(mediaId);
		},
		[onSelectMedia]
	);

	return (
		<Fragment>
			<Fragment>
				<InspectorControls>
					<PanelBody title={__('Block Display', 'stellarpay')} initialOpen={true}>
						<PanelRow>
							<RadioControl
								label={__('Display Options', 'stellarpay')}
								help={__('This option adjusts the content displayed in the block.', 'stellarpay')}
								selected={blockType}
								options={[
									{label: 'Repository', value: 'repository'},
									{label: 'Profile', value: 'profile'},
								]}
								onChange={(newBlockType) => {
									setAttributes({blockType: newBlockType});
								}}
							/>
						</PanelRow>
					</PanelBody>
					{blockType === 'repository' && (
						<PanelBody title={__('Repository Settings', 'stellarpay')} initialOpen={false}>
							<PanelRow>
								<TextControl
									label={__('Repo URL', 'stellarpay')}
									value={repoUrl}
									help={__(
										'Please enter the URL contents after the `https://github.com/` string. For example: `impress-org/givewp`.',
										'stellarpay'
									)}
									onChange={(newRepoUrl) => {
										setAttributes({repoUrl: newRepoUrl});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<TextControl
									label={__('Customize Repo Name', 'stellarpay')}
									value={customTitle}
									help={__(
										'Enter text to customize the title. Leave blank to use the default repo name.',
										'stellarpay'
									)}
									onChange={(newCustomTitle) => {
										setAttributes({customTitle: newCustomTitle});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<CheckboxControl
									label={__('Show tags', 'stellarpay')}
									checked={showTags}
									onChange={(newShowTags) => {
										setAttributes({showTags: newShowTags});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<CheckboxControl
									label={__('Show forks', 'stellarpay')}
									checked={showForks}
									onChange={(newShowForks) => {
										setAttributes({showForks: newShowForks});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<CheckboxControl
									label={__('Show subscribers', 'stellarpay')}
									checked={showSubscribers}
									onChange={(newShowSubsribers) => {
										setAttributes({showSubscribers: newShowSubsribers});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<CheckboxControl
									label={__('Show open issues', 'stellarpay')}
									checked={showOpenIssues}
									onChange={(newShowOpenIssues) => {
										setAttributes({showOpenIssues: newShowOpenIssues});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<CheckboxControl
									label={__('Show last update', 'stellarpay')}
									checked={showLastUpdate}
									onChange={(newShowLastUpdate) => {
										setAttributes({showLastUpdate: newShowLastUpdate});
									}}
								/>
							</PanelRow>
						</PanelBody>
					)}
					{blockType === 'profile' && (
						<PanelBody title={__('Profile Settings', 'stellarpay')} initialOpen={false}>
							<PanelRow>
								<TextControl
									label={__('GitHub Username', 'stellarpay')}
									value={profileName}
									help={__(
										'Please enter a GitHub profile username to display for this block.',
										'stellarpay'
									)}
									onChange={(newProfileName) => {
										setAttributes({profileName: newProfileName});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<div className="bfg-media-uploader">
									<p className={'bfg-label'}>
										<label>{__('Header background image', 'stellarpay')}</label>
									</p>
									<MediaUploadCheck>
										<MediaUpload
											onSelect={onSelectMedia}
											value={attributes.mediaId}
											allowedTypes={['image']}
											render={({open}) => (
												<Button
													className={
														attributes.mediaId === 0
															? 'editor-post-featured-image__toggle'
															: 'editor-post-featured-image__preview'
													}
													onClick={open}
												>
													{attributes.mediaId === 0 && __('Choose an image', 'stellarpay')}
													{media !== undefined && (
														<ResponsiveWrapper
															naturalWidth={media.media_details.width}
															naturalHeight={media.media_details.height}
														>
															<img src={media.source_url} />
														</ResponsiveWrapper>
													)}
												</Button>
											)}
										/>
									</MediaUploadCheck>
									<div className="bfg-media-btns">
										{attributes.mediaId !== 0 && (
											<MediaUploadCheck>
												<MediaUpload
													title={__('Replace image', 'stellarpay')}
													value={attributes.mediaId}
													onSelect={onSelectMedia}
													allowedTypes={['image']}
													render={({open}) => (
														<Button
															onClick={open}
															isSmall
															variant="secondary"
															className={'bfg-replace-image-btn'}
														>
															{__('Replace image', 'stellarpay')}
														</Button>
													)}
												/>
											</MediaUploadCheck>
										)}
										{attributes.mediaId !== 0 && (
											<MediaUploadCheck>
												<Button onClick={removeMedia} isSmall variant="secondary">
													{__('Remove image', 'stellarpay')}
												</Button>
											</MediaUploadCheck>
										)}
									</div>
									<p className={'bfg-help-text'}>
										{__('Upload or select an image for the header background.', 'stellarpay')}
									</p>
								</div>
							</PanelRow>
							<PanelRow>
								<TextControl
									label={__('Customize Profile Name', 'stellarpay')}
									value={customTitle}
									help={__(
										'Enter text to customize the title. Leave blank to use the default profile name.',
										'stellarpay'
									)}
									onChange={(newCustomTitle) => {
										setAttributes({customTitle: newCustomTitle});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<CheckboxControl
									label={__('Show bio', 'stellarpay')}
									checked={showBioChecked}
									onChange={(newShowBio) => {
										setShowBio(newShowBio);
										setAttributes({showBio: newShowBio});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<CheckboxControl
									label={__('Show location', 'stellarpay')}
									checked={showLocationChecked}
									onChange={(newShowLocation) => {
										setShowLocation(newShowLocation);
										setAttributes({showLocation: newShowLocation});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<CheckboxControl
									label={__('Show organization', 'stellarpay')}
									checked={showOrgChecked}
									onChange={(newShowOrg) => {
										setShowOrg(newShowOrg);
										setAttributes({showOrg: newShowOrg});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<CheckboxControl
									label={__('Show website', 'stellarpay')}
									checked={showWebsiteChecked}
									onChange={(newShowWebsite) => {
										setShowWebsite(newShowWebsite);
										setAttributes({showWebsite: newShowWebsite});
									}}
								/>
							</PanelRow>
							<PanelRow>
								<CheckboxControl
									label={__('Show Twitter', 'stellarpay')}
									checked={showTwitterChecked}
									onChange={(newShowTwitter) => {
										setShowTwitter(newShowTwitter);
										setAttributes({showTwitter: newShowTwitter});
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
