## How to Get Google Client ID and Client Secret

Adding Google sign-in to your website can save your customers' time. Before integrating Google sign-in, you need to create a Google Client ID and Client Secret. Follow these simple steps:

1. Go to the [Google Developers Console](https://console.developers.google.com/).

2. Click the "Select a project" dropdown ➝ "New Project" ➝ click the "Create" button.

3. Enter a name for your project ➝ click the "Create" button.

4. In the left-side menu, click "OAuth consent screen" ➝ choose the appropriate "User Type" ➝ click the "Create" button.

5. Add your "Application name," "Support email," "Authorized domain," and "Developer content information" ➝ click "Save and Continue."

6. Complete all four steps in the OAuth consent screen ➝ click "Back to Dashboard."

7. Go to "Credentials" ➝ click "Create Credentials" ➝ select "OAuth client ID" from the dropdown list.

8. In the "Application type" dropdown, select "Web application" ➝ enter a name for your OAuth 2.0 client.

9. In "Authorized JavaScript origins," enter your site URL ➝ in "Authorized redirect URIs," enter the page URL where users will be redirected after authentication ➝ click the "Create" button.

   - **Authorized JavaScript Origins**: `https://yoursite.com`
   - **Authorized Redirect URL**: `https://yoursite.com/Login/googleRedirect`

10. Copy your "Client ID" and "Client Secret."

11. Open your `other_config.php` file. Set `GOOGLE_LOGIN_ALLOWED` to `1`, and paste your `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`.

With these steps completed, you'll have the Google Client ID and Client Secret ready for integrating Google sign-in into your website.