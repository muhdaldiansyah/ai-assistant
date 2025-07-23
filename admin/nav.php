<nav style="
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid #e5e5e5;
    position: sticky;
    top: 0;
    z-index: 100;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
">
  <div style="
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
  ">
    <a href="../../index.php" style="
      font-size: 18px;
      font-weight: 700;
      color: #171717;
      text-decoration: none;
      letter-spacing: -0.025em;
    ">AI Assistant</a>
    
    <div style="
      display: flex;
      align-items: center;
      gap: 8px;
    ">
      <a href="../chat/" style="
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 8px;
        color: <?php echo (strpos($_SERVER['REQUEST_URI'], '/chat/') !== false) ? '#0070f3' : '#737373'; ?>;
        background: <?php echo (strpos($_SERVER['REQUEST_URI'], '/chat/') !== false) ? 'rgba(0, 112, 243, 0.1)' : 'transparent'; ?>;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
      " onmouseover="if (this.style.background === 'transparent') this.style.background = '#fafafa'" 
         onmouseout="if ('<?php echo strpos($_SERVER['REQUEST_URI'], '/chat/') !== false ? 'active' : 'inactive'; ?>' !== 'active') this.style.background = 'transparent'">
        <span>Chat</span>
      </a>
      
      <a href="../knowledge/" style="
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 8px;
        color: <?php echo (strpos($_SERVER['REQUEST_URI'], '/knowledge/') !== false) ? '#0070f3' : '#737373'; ?>;
        background: <?php echo (strpos($_SERVER['REQUEST_URI'], '/knowledge/') !== false) ? 'rgba(0, 112, 243, 0.1)' : 'transparent'; ?>;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
      " onmouseover="if (this.style.background === 'transparent') this.style.background = '#fafafa'" 
         onmouseout="if ('<?php echo strpos($_SERVER['REQUEST_URI'], '/knowledge/') !== false ? 'active' : 'inactive'; ?>' !== 'active') this.style.background = 'transparent'">
        <span>Documents</span>
      </a>
      
      <a href="../prompt/" style="
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 8px;
        color: <?php echo (strpos($_SERVER['REQUEST_URI'], '/prompt/') !== false) ? '#0070f3' : '#737373'; ?>;
        background: <?php echo (strpos($_SERVER['REQUEST_URI'], '/prompt/') !== false) ? 'rgba(0, 112, 243, 0.1)' : 'transparent'; ?>;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
      " onmouseover="if (this.style.background === 'transparent') this.style.background = '#fafafa'" 
         onmouseout="if ('<?php echo strpos($_SERVER['REQUEST_URI'], '/prompt/') !== false ? 'active' : 'inactive'; ?>' !== 'active') this.style.background = 'transparent'">
        <span>Settings</span>
      </a>
      
      <div style="width: 1px; height: 20px; background: #e5e5e5; margin: 0 8px;"></div>
      
      <a href="../../auth/api/logout.php" style="
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 8px;
        color: #737373;
        background: transparent;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
      " onmouseover="this.style.background = '#fafafa'; this.style.color = '#171717'" 
         onmouseout="this.style.background = 'transparent'; this.style.color = '#737373'">
        <span>Logout</span>
      </a>
    </div>
  </div>
</nav>

<style>
/* Google Fonts Inter - NOTE: Consider adding font files locally or use system fonts */
/* @import url('assets/fonts/inter.css'); */
</style>