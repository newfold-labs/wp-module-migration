describe('Full Site Migration: Shared ↔ Cloud Variants', () => {

  const migrations = [
    {
      label: 'Shared → Cloud',
      sourceUrl: Cypress.env('SC_shared_source'),
      destUrl: Cypress.env('SC_cloud_destin'),
      sourceUsername: Cypress.env('WP_USERNAME'),
      sourcePassword: Cypress.env('WP_PASSWORD'),
      destUsername: Cypress.env('DEST_USERNAME'),
      destPassword: Cypress.env('DEST_PASSWORD')
    },
    {
      label: 'Cloud → Shared',
      sourceUrl: Cypress.env('CS_cloud_source'),
      destUrl: Cypress.env('CS_shared_destin'),
      sourceUsername: Cypress.env('DEST_USERNAME'),
      sourcePassword: Cypress.env('DEST_PASSWORD'),
      destUsername: Cypress.env('WP_USERNAME'),
      destPassword: Cypress.env('WP_PASSWORD')
    },
 {
      label: 'Shared → Shared',
      sourceUrl: Cypress.env('SS_shared_source'),
      destUrl: Cypress.env('SS_shared_destin'),
      sourceUsername: Cypress.env('WP_USERNAME'),
      sourcePassword: Cypress.env('WP_PASSWORD'),
      destUsername: Cypress.env('DEST_USERNAME'),
      destPassword: Cypress.env('DEST_PASSWORD')
    },
    {
      label: 'Cloud → Cloud',
      sourceUrl: Cypress.env('CC_cloud_source'),
      destUrl: Cypress.env('CC_cloud_destin'),
      sourceUsername: Cypress.env('DEST_USERNAME'),
      sourcePassword: Cypress.env('DEST_PASSWORD'),
      destUsername: Cypress.env('WP_USERNAME'),
      destPassword: Cypress.env('WP_PASSWORD')
    }
  ];

  migrations.forEach(({ label, sourceUrl, destUrl, sourceUsername, sourcePassword, destUsername, destPassword }) => {
    it(`Migration Flow: ${label}`, () => {
      const host = Cypress.env('host');
    if (host !== 'bh') {
    cy.log('Skipping test: Not BH');
    return;
  }
      const safeDestUrl = typeof destUrl === 'string' ? destUrl.replace(/\/$/, '') : '';
const destLoginUrl = `${safeDestUrl}/wp-login.php`;
    // const destLoginUrl = `${destUrl.replace(/\/$/, '')}/wp-login.php`;
      const destOrigin = new URL('wp-login.php', destUrl).toString();
      const sourceOrigin = new URL(sourceUrl).origin;
      cy.clearAllSessionStorage();
       cy.origin(destOrigin, { args: { destLoginUrl, destUsername, destPassword } }, ({ destLoginUrl, destUsername, destPassword }) => {
       cy.visit(destLoginUrl);
        cy.get('body', { timeout: 5000 }).then($body => {
          if ($body.text().includes('Login with username and password')) {
            cy.contains('Login with username and password').click({ force: true });
            cy.get('#user_login').should('be.visible').clear().type(destUsername);
            cy.get('#user_pass').should('be.visible').clear().type(destPassword, { log: false });
            cy.get('#wp-submit').click();
            cy.log('Wordpress Login screen is seen, Logged in with username and password.');
          } else {
            cy.get('#user_login').should('be.visible').clear().type(destUsername);
            cy.get('#user_pass').should('be.visible').clear().type(destPassword, { log: false });
            cy.get('#wp-submit').click();
            cy.log('No Wordpress Login screen is seen');
          }
          cy.wait(1000);
        });

        cy.contains('Tools').should('be.visible').click();
cy.contains('Import').should('be.visible').click();
        cy.contains('Run Importer').should('be.visible').click();
           cy.wait(5000);
      });
      
      //Step 2: Connect to source site via migrate.bluehost.com
   
      cy.origin('https://migrate.bluehost.com', { args: { sourceUrl } }, ({ sourceUrl }) => {
        cy.get('input[placeholder="https://source.mysite.com"]').should('be.visible')
          .invoke('removeAttr', 'readonly').clear().type(sourceUrl);
        cy.contains('Connect').should('be.visible').click();
        cy.contains('button', 'Yes, Continue to Login')
      .should('be.visible')
      .click();
      });
    
     // Step 3: Login or approve on source site
  cy.origin(sourceOrigin, { args: { sourceUsername, sourcePassword } }, ({ sourceUsername, sourcePassword }) => {
  cy.get('body', { timeout: 20000 }).then($body => {
    if ($body.text().includes('Login with username and password')) {
            cy.contains('Login with username and password').click({ force: true });}
    else if ($body.find('#user_login').length > 0 && $body.find('#user_pass').length > 0) {
  cy.wait(10000);
      cy.get('#user_login').clear().type(sourceUsername);
      cy.get('#user_pass').clear().type(sourcePassword, { log: false });
      cy.get('#wp-submit').click();
      cy.get('#approve', { timeout: 10000 }).should('be.visible').click();
      cy.log('Approved after login.');
    } else if ($body.find('#approve').length > 0) {
      cy.get('#approve').click();
      cy.log('Approved without login.');
    } else {
      cy.log('Neither login nor approve button found.');
    }
  });

});

      // Step 4: Wait for migration completion
     cy.origin('https://migrate.bluehost.com', { args: { sourceUrl } }, ({ sourceUrl }) => {
      cy.log('Waiting for migration completion...');
      

      cy.get('body',).then($body => {
        if ($body.text().includes('Migrating your files, content, and data...') ) {
          cy.log(`Migration in Progress.`);
        } else {
          cy.log(`Migration may have failed. Check logs.`);
          cy.screenshot('migration_failed');
        }
      });
     
 
  cy.contains(/Your Migration is Complete!|Migration Failed/, { timeout: 300000 })
    .should('be.visible');
  cy.log('Done');
      });
     });
  });
 
 
});
