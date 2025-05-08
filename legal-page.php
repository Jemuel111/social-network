<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zyntra</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: #1c1e21;
        }
        .header {
            background-color: #1877f2;
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
        }
        .logo img {
            height: 30px;
            margin-right: 10px;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #65676b;
            font-size: 14px;
        }
        .footer a {
            color: #1877f2;
            text-decoration: none;
            margin: 0 10px;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
        }
        .tab.active {
            background-color: #1877f2;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        h1 {
            color: #1877f2;
            font-size: 24px;
        }
        h2 {
            color: #1c1e21;
            font-size: 20px;
        }
        p, ul li {
            line-height: 1.5;
        }
        .back-button {
            background-color: #1877f2;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 20px;
        }
        .back-button:hover {
            background-color: #166fe5;
        }
        .accordion {
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .accordion-header {
            padding: 15px;
            background-color: #f5f6f7;
            cursor: pointer;
            font-weight: bold;
        }
        .accordion-content {
            padding: 15px;
            display: none;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
     <img src="assets/images/zyntra-logo.png" alt="Zyntra logo">
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
            <p>Welcome to Zyntra. We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform.</p>

            <h2>2. Information We Collect</h2>
            <p>We collect information that you provide directly to us, including:</p>
            <ul>
                <li>Account information (name, email address, password, date of birth)</li>
                <li>Profile information (profile picture, bio, location)</li>
                <li>Content you post (posts, comments, photos)</li>
                <li>Communications (messages with other users)</li>
                <li>Transaction data (if you purchase premium features)</li>
            </ul>
            <p>We also automatically collect certain information when you use our platform, including:</p>
            <ul>
                <li>Device information (IP address, browser type, operating system)</li>
                <li>Usage data (pages visited, time spent, clicks)</li>
                <li>Location data (with your permission)</li>
                <li>Cookies and similar tracking technologies</li>
            </ul>

            <h2>3. How We Use Your Information</h2>
            <p>We use your information for various purposes, including to:</p>
            <ul>
                <li>Provide, maintain, and improve our platform</li>
                <li>Process your transactions</li>
                <li>Communicate with you about updates and promotions</li>
                <li>Personalize your experience and content</li>
                <li>Analyze how you use our platform</li>
                <li>Protect against fraud and unauthorized access</li>
                <li>Comply with legal obligations</li>
            </ul>

            <h2>4. Information Sharing</h2>
            <p>We may share your information with:</p>
            <ul>
                <li>Other users (according to your privacy settings)</li>
                <li>Service providers (for hosting, analytics, payment processing)</li>
                <li>Business partners (with your consent)</li>
                <li>Legal authorities (when required by law)</li>
            </ul>

            <h2>5. Your Choices and Rights</h2>
            <p>You have several rights regarding your personal information:</p>
            <ul>
                <li>Access and update your information</li>
                <li>Control your privacy settings</li>
                <li>Delete your account</li>
                <li>Opt out of marketing communications</li>
                <li>Request a copy of your data</li>
            </ul>

            <h2>6. Data Security</h2>
            <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

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
            <p>To use certain features of our platform, you must register for an account. You agree to provide accurate, current, and complete information during the registration process and to update such information to keep it accurate, current, and complete.</p>
            <p>You are responsible for safeguarding your password and for all activities that occur under your account. You agree to notify us immediately of any unauthorized use of your account.</p>

            <h2>3. User Content</h2>
            <p>You retain ownership of the content you post on Zyntra. By posting content, you grant us a non-exclusive, transferable, sub-licensable, royalty-free, worldwide license to use, modify, publicly display, reproduce, and distribute such content on and through our platform.</p>
            <p>You represent and warrant that:</p>
            <ul>
                <li>You own or have the necessary rights to the content you post</li>
                <li>Your content does not violate the rights of any third party</li>
                <li>Your content complies with these Terms and applicable laws</li>
            </ul>

            <h2>4. Prohibited Conduct</h2>
            <p>You agree not to:</p>
            <ul>
                <li>Use our platform for any illegal purpose</li>
                <li>Post content that is harmful, abusive, threatening, or harassing</li>
                <li>Impersonate any person or entity</li>
                <li>Use automated means to access or collect data from our platform</li>
                <li>Interfere with or disrupt our platform or servers</li>
                <li>Sell, trade, or transfer your account to another party</li>
            </ul>

            <h2>5. Intellectual Property</h2>
            <p>Our platform and its original content, features, and functionality are owned by Zyntra and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.</p>

            <h2>6. Termination</h2>
            <p>We may terminate or suspend your account and access to our platform immediately, without prior notice or liability, for any reason, including if you breach these Terms.</p>

            <h2>7. Limitation of Liability</h2>
            <p>In no event shall Zyntra be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use our platform.</p>

            <h2>8. Changes to Terms</h2>
            <p>We reserve the right to modify or replace these Terms at any time. We will notify you of any changes by posting the new Terms on this page and updating the "Last updated" date.</p>

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
                <div class="accordion-header" onclick="toggleAccordion(this)">How do I update my profile?</div>
                <div class="accordion-content">
                    <p>To update your profile:</p>
                    <ol>
                        <li>Click on "Profile" in the left sidebar</li>
                        <li>Click "View Profile" to see your current profile</li>
                        <li>Click "Edit Profile" to make changes</li>
                        <li>Update your information such as profile picture, bio, or personal details</li>
                        <li>Click "Save Changes" to update your profile</li>
                    </ol>
                </div>
            </div>
            
            <div class="accordion">
                <div class="accordion-header" onclick="toggleAccordion(this)">How do I manage my privacy settings?</div>
                <div class="accordion-content">
                    <p>To manage your privacy settings:</p>
                    <ol>
                        <li>Click on your profile picture in the top right</li>
                        <li>Select "Settings & Privacy" from the dropdown menu</li>
                        <li>Click "Privacy Settings"</li>
                        <li>Adjust who can see your posts, profile information, and friend list</li>
                        <li>Click "Save Changes" when done</li>
                    </ol>
                </div>
            </div>
            
            <div class="accordion">
                <div class="accordion-header" onclick="toggleAccordion(this)">How do I delete my account?</div>
                <div class="accordion-content">
                    <p>To delete your account:</p>
                    <ol>
                        <li>Click on your profile picture in the top right</li>
                        <li>Select "Settings & Privacy" from the dropdown menu</li>
                        <li>Click "Account Settings"</li>
                        <li>Scroll down and click "Delete Account"</li>
                        <li>Follow the prompts to confirm deletion</li>
                        <li>Note that account deletion is permanent and cannot be undone</li>
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