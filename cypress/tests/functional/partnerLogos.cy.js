describe('Tests the Partner Logos plugin', function() {

  it('Enables the plugin', function() {
    cy.login('admin', 'admin', 'publicknowledge')
    cy.get('a:contains("Settings")')
      .click();
    cy.get('a:contains("Website")')
      .click();
    cy.get('button[id="plugins-button"]')
      .click()
    cy.get('input[id^="select-cell-partnerlogosplugin-enabled"]')
      .click()
    cy.get(`div:contains('The plugin "Partner Logos" has been enabled.')`)
    cy.visit('/')
  })

  it('Tests the plugin without any configuration', function () {
    cy.visit('/')
    cy.get('.pkp_brand_footer')
  });

  it('Uploads a logo', function() {
    cy.login('admin', 'admin', 'publicknowledge')
    cy.get('a:contains("Settings")')
      .click();
    cy.get('a:contains("Workflow")')
      .click()
    cy.get('button[id="library-button"]')
      .click()
    cy.get('a:contains("Add a file")')
      .click()
    cy.get('input[name^="libraryFileName[en"]')
      .type('Example Logo')
    cy.get('select[name="fileType"]')
      .select('Partner Logos')
    cy.get('input[name="publicAccess"]')
      .check()
    cy.get('input[type="file"]')
      .selectFile(
        'plugins/generic/partnerLogos/cypress/fixtures/example-logo.png',
        {force: true}
      )
    cy.wait(1000)
    cy.get('#uploadForm button:contains("OK")')
      .click()
    cy.get('#libraryGridDiv a:contains("Example Logo")')
  })

  it('Adds the partner logos to about the journal', function() {
    // Make sure the Insert Content button is visible in the
    // tinymce field
    cy.viewport(1280, 833)
    cy.login('admin', 'admin', 'publicknowledge')
    cy.get('a:contains("Settings")')
      .click();
		cy.get('nav')
      .contains('Journal')
      .click({ force: true }); // Clicks first one, even though two are found (Statistics > Journal)
    cy.get('#masthead-about-description-en + .pkpFormField__control button:contains("Insert Content")')
      .click()
    cy.get('#insert-content-partnerLogos button:contains("Insert")')
      .click()
    cy.get('#masthead button:contains("Save")')
      .click()
    cy.visit('/index.php/publicknowledge/en/about')
    cy.get('.pl-partner-logos .pl-partner-logo .pl-partner-logo-img')
  })
})