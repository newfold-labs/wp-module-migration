import { ArrowLongRightIcon } from '@heroicons/react/24/outline';

const DashboardMigrate = ( {} ) => {
	const connectMigrate = () => {
		// eslint-disable-next-line no-console
		getMigrateRedirectUrl().then( ( res ) => {
			// eslint-disable-next-line no-console
			window.open( res?.data?.redirect_url, '_self' );
		} );
	};
	return (
		<div className=" nfd-rounded nfd-border nfd-p-4 nfd-grid nfd-grid-cols-2 nfd-gap-4">
			<div className="nfd-grid nfd-grid-rows-[repeat(2,_min-content)] nfd-gap-6">
				<div>
					<h1 className="nfd-title nfd-title--2">
						{
							"	Let's migrate your existing site to your new account"
						}
					</h1>
					<p>
						Migrating your site is easy with our free site import
						tool. Or, you can pay to have our Migrations Team
						experts handle your transfer for you.{ ' ' }
						<a href="testing" target="_blank">
							Learn more
						</a>
						.
					</p>
				</div>
				<button className="nfd-text-primary nfd-text-left">
					Dismiss this card
				</button>
			</div>
			<div className="nfd-flex nfd-items-center nfd-justify-end">
				<button
					type="button"
					onClick={ () => connectMigrate() }
					className="nfd-button nfd-button--primary nfd-flex nfd-gap-2"
				>
					Migrate your site{ ' ' }
					<ArrowLongRightIcon className="nfd-w-[1.125rem]" />
				</button>
			</div>
		</div>
	);
};

export default DashboardMigrate;
