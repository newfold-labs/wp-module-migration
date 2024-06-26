import { MIGRATE_CONNECT } from './constants';
import apiFetch from '@wordpress/api-fetch';

export const getMigrateRedirectUrl = () => {
	return apiFetch( {
		url: `${ MIGRATE_CONNECT }`,
		headers: {
			method: 'GET',
			'content-type': 'application/json',
			'Accept-Encoding': 'gzip, deflate, br',
			'Access-Control-Allow-Origin': '*',
		},
	} );
};
