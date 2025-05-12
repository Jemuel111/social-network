<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - Zyntra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #312E5E;
            color: #ffffff;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background-color: #2C2756;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #8C86A3;
            font-size: 14px;
            background-color: #312E5E;
        }
        .footer a {
            color: #8A4FFF;
            text-decoration: none;
            margin: 0 10px;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #4A4475;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
            color: #8C86A3;
        }
        .tab.active {
            background: linear-gradient(135deg, #8A4FFF, #5E2BFF);
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        h1 {
            color: #8A4FFF;
            font-size: 24px;
        }
        h2 {
            color: #8A4FFF;
            font-size: 20px;
        }
        p, ul li {
            line-height: 1.5;
            color: #E0E0E0;
        }
        .back-button {
            background: linear-gradient(135deg, #8A4FFF, #5E2BFF);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 20px;
        }
        .back-button:hover {
            opacity: 0.9;
        }
        .accordion {
            margin-bottom: 10px;
            border: 1px solid #4A4475;
            border-radius: 4px;
            background-color: #312E5E;
        }
        .accordion-header {
            padding: 15px;
            background-color: #2C2756;
            cursor: pointer;
            font-weight: bold;
            color: #8C86A3;
        }
        .accordion-content {
            padding: 15px;
            display: none;
            border-top: 1px solid #4A4475;
            background-color: #312E5E;
        }
        /* Mobile Navigation */
        @media (max-width: 992px) {
            .custom-navbar {
                margin-bottom: 15px;
            }

            .navbar-brand {
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/images/logo-white.png" alt="" class="img-fluid" style="max-height: 50px; margin-right: 15px;">
            <h5 class="brand-text">ZYNTRA</h5>
        </a>
    </div>


    <div class="container">
        <div class="tabs">
            <div class="tab active" onclick="openTab('privacy')">Privacy Policy</div>
            <div class="tab" onclick="openTab('terms')">Terms of Service</div>
            <div class="tab" onclick="openTab('help')">Help Center</div>
        </div>

        <div id="privacy" class="tab-content active">
            <h1>Privacy Policy</h1>
            <p>Last updated: May 3, 2025</p>

            <h2>1. Introduction</h2>
            <p>Welcome to Zyntra. We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our social networking platform.</p>

            <h2>2. Information We Collect</h2>
            <p>We collect information that you provide directly to us, including:</p>
            <ul>
                <li>Account information (name, email address, password)</li>
                <li>Profile information (profile picture, bio, location)</li>
                <li>Content you post (text posts, images)</li>
                <li>Social interactions (likes, comments, shares)</li>
                <li>Friend connections and friend requests</li>
            </ul>
            <p>We also automatically collect certain information when you use our platform, including:</p>
            <ul>
                <li>Device information (IP address, browser type)</li>
                <li>Usage data (posts viewed, interactions)</li>
                <li>Cookies and similar tracking technologies</li>
            </ul>

            <h2>3. How We Use Your Information</h2>
            <p>We use your information to:</p>
            <ul>
                <li>Provide and maintain our social networking services</li>
                <li>Show you relevant content and friend suggestions</li>
                <li>Enable social features (likes, comments, shares)</li>
                <li>Manage friend connections and requests</li>
                <li>Protect against unauthorized access</li>
                <li>Improve our platform and user experience</li>
            </ul>

            <h2>4. Information Sharing</h2>
            <p>Your information is shared as follows:</p>
            <ul>
                <li>Your posts and profile information are visible to your friends</li>
                <li>Your likes and comments are visible to friends of the post author</li>
                <li>Friend suggestions are based on mutual connections</li>
                <li>We do not sell your personal information to third parties</li>
            </ul>

            <h2>5. Your Privacy Controls</h2>
            <p>You can control your privacy by:</p>
            <ul>
                <li>Managing your friend connections</li>
                <li>Blocking users you don't want to interact with</li>
                <li>Editing or deleting your posts</li>
                <li>Updating your profile information</li>
                <li>Deleting your account</li>
            </ul>

            <h2>6. Data Security</h2>
            <p>We implement security measures to protect your information, including:</p>
            <ul>
                <li>Secure password storage</li>
                <li>Protected friend connections</li>
                <li>Safe post sharing mechanisms</li>
                <li>Regular security updates</li>
            </ul>

            <h2>7. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date.</p>

            <button class="back-button" onclick="window.history.back()">Back to Zyntra</button>
        </div>

        <div id="terms" class="tab-content">
            <h1>Terms of Service</h1>
            <p>Last updated: May 3, 2025</p>

            <h2>1. Acceptance of Terms</h2>
            <p>By accessing or using Zyntra, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this platform.</p>

            <h2>2. Account Registration</h2>
            <p>To use Zyntra, you must register for an account. You agree to:</p>
            <ul>
                <li>Provide accurate and complete information</li>
                <li>Keep your account credentials secure</li>
                <li>Notify us immediately of any unauthorized access</li>
                <li>Not share your account with others</li>
            </ul>

            <h2>3. User Content</h2>
            <p>You retain ownership of the content you post on Zyntra. By posting content, you grant us a license to:</p>
            <ul>
                <li>Display your posts to your friends</li>
                <li>Enable social features (likes, comments, shares)</li>
                <li>Show your content in friend feeds</li>
                <li>Store and manage your content</li>
            </ul>
            <p>You are responsible for ensuring your content:</p>
            <ul>
                <li>Does not violate any laws</li>
                <li>Is not harmful or offensive</li>
                <li>Does not infringe on others' rights</li>
                <li>Complies with our community guidelines</li>
            </ul>

            <h2>4. Social Features</h2>
            <p>Our platform includes the following social features:</p>
            <ul>
                <li>Friend connections and requests</li>
                <li>Post creation and sharing</li>
                <li>Likes and comments</li>
                <li>User blocking</li>
            </ul>
            <p>You agree to use these features responsibly and respectfully.</p>

            <h2>5. Prohibited Conduct</h2>
            <p>You agree not to:</p>
            <ul>
                <li>Post harmful or offensive content</li>
                <li>Harass or bully other users</li>
                <li>Impersonate others</li>
                <li>Spam or send unwanted friend requests</li>
                <li>Attempt to access others' accounts</li>
            </ul>

            <h2>6. Account Termination</h2>
            <p>We may terminate or suspend your account if you:</p>
            <ul>
                <li>Violate these Terms of Service</li>
                <li>Engage in prohibited conduct</li>
                <li>Create multiple accounts</li>
                <li>Abuse our social features</li>
            </ul>

            <h2>7. Changes to Terms</h2>
            <p>We reserve the right to modify these Terms at any time. We will notify you of any changes by posting the new Terms on this page and updating the "Last updated" date.</p>

            <button class="back-button" onclick="window.history.back()">Back to Zyntra</button>
        </div>

        <div id="help" class="tab-content">
            <h1>Help Center</h1>
            
            <h2>Frequently Asked Questions</h2>
            
            <div class="accordion">
                <div class="accordion-header" onclick="toggleAccordion(this)">How do I create a post?</div>
                <div class="accordion-content">
                    <p>To create a post:</p>
                    <ol>
                        <li>Go to your home page or profile page</li>
                        <li>Click on the text box that says "What's on your mind?"</li>
                        <li>Type your message</li>
                        <li>Optionally, click "Add Photo" to include an image</li>
                        <li>Click the "Post" button to publish</li>
                    </ol>
                </div>
            </div>
            
            <div class="accordion">
                <div class="accordion-header" onclick="toggleAccordion(this)">How do I add friends?</div>
                <div class="accordion-content">
                    <p>To add friends on Zyntra:</p>
                    <ol>
                        <li>Check the "Friend Suggestions" section on your home page</li>
                        <li>Click the "+" button next to someone you want to add</li>
                        <li>Alternatively, search for users using the search box at the top</li>
                        <li>Visit a user's profile and click "Add Friend"</li>
                    </ol>
                </div>
            </div>
            
            <div class="accordion">
                <div class="accordion-header" onclick="toggleAccordion(this)">How do I manage my posts?</div>
                <div class="accordion-content">
                    <p>To manage your posts:</p>
                    <ol>
                        <li>Find the post you want to manage</li>
                        <li>Click the three dots menu (⋮) in the top right of the post</li>
                        <li>Choose to edit or delete the post</li>
                        <li>For editing, make your changes and click "Save"</li>
                        <li>For deletion, confirm your choice</li>
                    </ol>
                </div>
            </div>
            
            <div class="accordion">
                <div class="accordion-header" onclick="toggleAccordion(this)">How do I block someone?</div>
                <div class="accordion-content">
                    <p>To block a user:</p>
                    <ol>
                        <li>Go to their profile page</li>
                        <li>Click the three dots menu (⋮)</li>
                        <li>Select "Block User"</li>
                        <li>Confirm your choice</li>
                        <li>Blocked users won't be able to see your content or interact with you</li>
                    </ol>
                </div>
            </div>
            
            <div class="accordion">
                <div class="accordion-header" onclick="toggleAccordion(this)">How do I interact with posts?</div>
                <div class="accordion-content">
                    <p>You can interact with posts in several ways:</p>
                    <ol>
                        <li>Click the heart icon to like a post</li>
                        <li>Click the comment icon to view or add comments</li>
                        <li>Click the share icon to share the post</li>
                        <li>Use the three dots menu (⋮) to report inappropriate content</li>
                    </ol>
                </div>
            </div>
            
            <div class="accordion">
                <div class="accordion-header" onclick="toggleAccordion(this)">How do I update my profile?</div>
                <div class="accordion-content">
                    <p>To update your profile:</p>
                    <ol>
                        <li>Click on your profile picture in the top right</li>
                        <li>Select "Edit Profile"</li>
                        <li>Update your profile picture, bio, or location</li>
                        <li>Click "Save Changes" when done</li>
                    </ol>
                </div>
            </div>
            
            <h2>Contact Support</h2>
            <p>If you couldn't find the answer to your question, please contact our support team:</p>
            <p>Email: support@zyntra.com</p>
            <p>Response time: Usually within 24 hours</p>
            
            <button class="back-button" onclick="window.history.back()">Back to Zyntra</button>
        </div>
    </div>

    <div class="footer">
        <p>Zyntra © 2025</p>
        <a href="#" onclick="openTab('privacy')">Privacy</a> • 
        <a href="#" onclick="openTab('terms')">Terms</a> • 
        <a href="#" onclick="openTab('help')">Help</a>
    </div>

    <script>
        function openTab(tabName) {
            // Hide all tab content
            var tabContents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            // Deactivate all tabs
            var tabs = document.getElementsByClassName("tab");
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            
            // Show the selected tab content
            document.getElementById(tabName).classList.add("active");
            
            // Activate the clicked tab
            for (var i = 0; i < tabs.length; i++) {
                if (tabs[i].textContent.toLowerCase().includes(tabName)) {
                    tabs[i].classList.add("active");
                }
            }
        }
        
        function toggleAccordion(element) {
            var content = element.nextElementSibling;
            if (content.style.display === "block") {
                content.style.display = "none";
            } else {
                content.style.display = "block";
            }
        }
    </script>
</body>
</html>
