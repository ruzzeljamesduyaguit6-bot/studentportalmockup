<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="pageTitle">Messages - Role Based System</title>
    <link rel="icon" href="/images/bright-futures-logo.png">
    <x-vite-assets :assets="['resources/css/app.css', 'resources/css/views.css', 'resources/js/app.js', 'resources/js/messages-loader.js']" />
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f5f5;display:flex;flex-direction:column;min-height:100vh}
        .navbar{display:flex;align-items:center;justify-content:space-between;padding:0 24px;height:60px;background:#fff;border-bottom:1px solid #e0e0e0;box-shadow:0 2px 4px rgba(0,0,0,.06);position:sticky;top:0;z-index:100}
        .navbar-title{display:flex;align-items:center;gap:10px;font-size:1.1rem;font-weight:700;color:#333}
        .navbar-logo{height:36px;width:36px;object-fit:contain}
        .navbar-actions{display:flex;align-items:center;gap:12px}
        .user-info{display:flex;align-items:center;gap:8px;font-size:.9rem;color:#555}
        .user-badge{width:32px;height:32px;border-radius:50%;background:#667eea;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700}
        .logout-btn{padding:6px 14px;background:#e53e3e;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.85rem}
        .page-wrapper{display:flex;flex:1}
        .sidebar{width:250px;background:linear-gradient(180deg,#f8f9fa 0%,#f0f1f3 100%);border-right:1px solid #ddd;padding:20px 0;overflow-y:auto;display:flex;flex-direction:column}
        .sidebar-brand{padding:0 20px 20px;font-weight:700;color:#333;font-size:1rem}
        .nav-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#555;text-decoration:none;font-size:.9rem;transition:background .2s}
        .nav-item:hover,.nav-item.active{background:#e8eaf6;color:#3949ab}
        .nav-icon{font-size:1.1rem}
        .main-container{flex:1;padding:24px;overflow-y:auto}
        .container{max-width:1100px;margin:0 auto}
        .hidden{display:none!important}
        .add-user-btn{padding:8px 16px;background:#667eea;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.9rem;font-weight:600}
        .messages-shell{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);overflow:hidden;display:flex;flex-direction:column;height:calc(100vh - 140px)}
        .messages-tabs{display:flex;border-bottom:1px solid #e0e0e0}
        .messages-tab{flex:1;padding:12px;background:none;border:none;cursor:pointer;font-size:.9rem;font-weight:600;color:#888;border-bottom:3px solid transparent}
        .messages-tab.active{color:#667eea;border-bottom-color:#667eea}
        .messages-panel{flex:1;display:flex;flex-direction:column;overflow:hidden}
        .messages-panel.hidden{display:none}
        .message-list{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:8px}
        .message-input-row{display:flex;gap:8px;padding:12px 16px;border-top:1px solid #e0e0e0}
        .message-input-row input{flex:1;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:.9rem}
        .private-layout{display:flex;flex:1;overflow:hidden}
        .private-users-column{width:240px;border-right:1px solid #e0e0e0;overflow-y:auto;display:flex;flex-direction:column}
        .private-search{padding:10px 14px;border:none;border-bottom:1px solid #e0e0e0;font-size:.9rem;outline:none}
        .private-users-list{flex:1;overflow-y:auto}
        .private-chat-column{flex:1;display:flex;flex-direction:column;overflow:hidden}
        .private-chat-header{padding:12px 16px;border-bottom:1px solid #e0e0e0;font-weight:600;font-size:.9rem;color:#555}
    </style>
</head>
<body>
    <div class="navbar" id="navbar">
        <h1 id="navbarTitle" class="navbar-title">
            <img class="navbar-logo" src="/images/bright-futures-logo.png" alt="Bright Futures School logo">
            <span id="navbarTitleText">Messages</span>
        </h1>
        <div class="navbar-actions">
            <div class="user-info">
                <span id="userName">User</span>
                <span class="user-badge" id="userInitials">U</span>
            </div>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
    </div>

    <div class="page-wrapper">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <span class="brand-icon"></span>
                <span class="brand-text">Dashboard</span>
            </div>

            <nav class="sidebar-nav">
                <a href="/dashboard" class="nav-item" data-page="dashboard" data-roles="admin,student,professor">
                    <span class="nav-icon">📊</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="/notifications" class="nav-item" data-page="notifications" data-roles="admin,student,professor">
                    <span class="nav-icon">🔔</span>
                    <span class="nav-label">Notifications</span>
                </a>
                <a href="/messages" class="nav-item active" data-page="messages" data-roles="admin,student,professor">
                    <span class="nav-icon">💬</span>
                    <span class="nav-label">Messages</span>
                </a>
                <a href="/analytics" class="nav-item" data-page="analytics" data-roles="admin">
                    <span class="nav-icon">📈</span>
                    <span class="nav-label">Assign Task</span>
                </a>
                <a href="/users" class="nav-item" data-page="roles" data-roles="admin">
                    <span class="nav-icon">👥</span>
                    <span class="nav-label">User Management</span>
                </a>
                <a href="/designations" class="nav-item" data-page="designations" data-roles="admin">
                    <span class="nav-icon">🏷️</span>
                    <span class="nav-label">Designations</span>
                </a>
                <a href="/departments" class="nav-item" data-page="departments" data-roles="admin">
                    <span class="nav-icon">🏛️</span>
                    <span class="nav-label">Departments</span>
                </a>
                <a href="/courses" class="nav-item" data-page="courses" data-roles="admin">
                    <span class="nav-icon">📚</span>
                    <span class="nav-label">Courses</span>
                </a>
                <a href="/subjects" class="nav-item" data-page="subjects" data-roles="admin">
                    <span class="nav-icon">🧾</span>
                    <span class="nav-label">Subjects</span>
                </a>
                <a href="/reports" class="nav-item" data-page="reports" data-roles="admin">
                    <span class="nav-icon">📋</span>
                    <span class="nav-label">Reports</span>
                </a>
                <a href="/profile" class="nav-item" data-page="reports" data-roles="student,professor">
                    <span class="nav-icon">👨‍💼</span>
                    <span class="nav-label">Profile</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="/settings" class="nav-item settings-item" data-page="settings" data-roles="admin,student,professor">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-label">Settings</span>
                </a>
            </div>
        </aside>

        <div class="main-container">
            <div class="container">
                <div class="messages-shell">
                    <div class="messages-tabs">
                        <button id="globalTab" class="messages-tab active" onclick="switchMessageTab('global')">Global Messages</button>
                        <button id="privateTab" class="messages-tab" onclick="switchMessageTab('private')">Private Messages</button>
                    </div>

                    <div id="globalPanel" class="messages-panel">
                        <div id="globalMessagesList" class="message-list"></div>
                        <form id="globalMessageForm" class="message-input-row">
                            <input id="globalMessageInput" type="text" placeholder="Write a message to everyone..." maxlength="2000" required>
                            <button type="submit" class="add-user-btn">Send</button>
                        </form>
                    </div>

                    <div id="privatePanel" class="messages-panel hidden">
                        <div class="private-layout">
                            <div class="private-users-column">
                                <input id="privateSearchInput" class="private-search" type="text" placeholder="Search by name or ID..." oninput="filterPrivateUsers()">
                                <div id="privateUsersList" class="private-users-list"></div>
                            </div>
                            <div class="private-chat-column">
                                <div id="privateChatHeader" class="private-chat-header">Select a user to start messaging</div>
                                <div id="privateMessagesList" class="message-list"></div>
                                <form id="privateMessageForm" class="message-input-row hidden">
                                    <input id="privateMessageInput" type="text" placeholder="Write a private message..." maxlength="2000" required>
                                    <button type="submit" class="add-user-btn">Send</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
