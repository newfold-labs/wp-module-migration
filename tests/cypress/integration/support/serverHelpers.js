export const setOption = ( key, value ) => {
	cy.exec( `npx wp-env run cli wp option set ${ key } '${ value }'` );
};

export const updateTransient = ( key, jsonValue ) => {
	const stringified = JSON.stringify( jsonValue ).replace( /"/g, '\\"' );
	cy.exec(
		`npx wp-env run cli wp option update ${ key } "${ stringified }" --format=json`
	);
};

export const deleteOption = ( key ) => {
	cy.exec( `npx wp-env run cli wp option delete ${ key }` );
};
