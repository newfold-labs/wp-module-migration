import { ArrowLongRightIcon } from '@heroicons/react/24/outline';
import { __ } from "@wordpress/i18n";

const content = {
	title: __( "Let's migrate your existing site to your new account", "wp-module-migration" ),
	description: __( "Migrating your site is easy with our free site import tool. Or, you can pay to have our Migrations Team experts handle your transfer for you.", "wp-module-migration" ),
	learnMoreText: __( "Learn more", "wp-module-migration" ),
	dismissButtonText: __( "Dismiss this card", "wp-module-migration" ),
	buttonText: __( "Migrate your site", "wp-module-migration" )
}

const DashboardMigrate = ( {} ) => {
	const connectMigrate = () => {
		getMigrateRedirectUrl().then( (res) => {
			window.open( res?.data?.redirect_url, '_self' );
		});
	};
	return (
		<div className=" nfd-rounded nfd-border nfd-p-4 nfd-grid nfd-grid-cols-2 nfd-gap-4">
			<div className="nfd-grid nfd-grid-rows-[repeat(2,_min-content)] nfd-gap-6">
				<div>
					<h1 className="nfd-title nfd-title--2">
						{ content.title }
					</h1>
					<p>
						<a href="#" target="_blank">
							{ content.learnMoreText }
						</a>.
					</p>
				</div>
				<button className="nfd-text-primary nfd-text-left">
					{ content.dismissButtonText }
				</button>
			</div>
			<div className="nfd-flex nfd-items-center nfd-justify-end">
				<button
					type="button"
					onClick={ () => connectMigrate() }
					className="nfd-button nfd-button--primary nfd-flex nfd-gap-2"
				>
					{ content.buttonText }
					<ArrowLongRightIcon className="nfd-w-[1.125rem]" />
				</button>
			</div>
		</div>
	);
};

export default DashboardMigrate;
