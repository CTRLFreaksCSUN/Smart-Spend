<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="SplashPage" content="CTRL_Freaks">
  <meta name="description" content="Proof of concept for Smart Spend splash page.">
  <title>Smart Spend - Take Control of Your Finances</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="Loginstyle.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>
  <div class="background-overlay"></div>
  <div class="container">
    <aside>
      <div class="logo-title">
        <img src="images/SmartSpendLogo.png" alt="Smart Spend Logo">
        <h2>Smart Spend</h2>
      </div>
      <nav>
        <a href="#how"><i class="fas fa-lightbulb"></i> How to Use</a>
        <a href="#about"><i class="fas fa-users"></i> About the Creators</a>
        <a href="LoginPage.php"><i class="fas fa-sign-in-alt"></i> Sign In</a>
      </nav>
    </aside>

    <main>
        <div class="mainSplash">
            <section class="section" id="how">
                <h2>How to Use Smart Spend</h2>
                <p>Smart Spend helps users upload receipts, invoices, or bank statements and intelligently categorize and analyze them. Users can set budget goals, track spending habits, and visualize financial trends through interactive dashboards. It’s designed for both budgeting beginners and financially savvy individuals looking for a modern tool.</p>
                <div class="slideshow-container">

                <div class="slide fade">
                    <img src="images/SignUp.png" alt="Smart Spend upload docs screenshot">
                    <div class="caption">Easy to set up, only takes a few minutes!</div>
                  </div>

                  <div class="slide fade">
                    <img src="images/Dashboard.png" alt="Smart Spend Dashboard Screenshot">
                    <div class="caption">The dashboard oveview gives you quick and easy to see statistics of your spending.</div>
                  </div>

                  <div class="slide fade">
                    <img src="images/AIChatbot.png" alt="Smart SPend AI Chatbot screenshot">
                    <div class="caption">You can ask the AI chat bot for any financial advice.</div>
                  </div>

                  <div class="slide fade">
                    <img src="images/EnterExpenses.png" alt="Smart Spend enter expenses screenshot">
                    <div class="caption">Enter your expenses as they occur.</div>
                  </div>

                  <div class="slide fade">
                    <img src="images/UploadDocs.png" alt="Smart Spend upload docs screenshot">
                    <div class="caption">Or instead, upload receipts and have the items automatically categorized.</div>
                  </div>
                  
                  <div class="slide fade">
                    <img src="images/ViewAnalysis.png" alt="Smart Spend analysis page">
                    <div class="caption">Track your budget goals.</div>
                  </div>

                  <a class="prev" onclick="changeSlide(-1)">&#10094;</a>
                  <a class="next" onclick="changeSlide(1)">&#10095;</a>
                </div>
            </section>

            <section class="section" id="about">
                <h2>About the Creators</h2>
                <p>This app was created by a passionate team focused on making financial planning more accessible through AI-driven tools. </p>
                <br>
                <ul>
                  <li>Jessica Babos: Project Manager, Editor, Tester, Analyst, Application Developer</li>
                  <li>Andrew Hua: Application Developer, Tester, Analyst</li>
                  <li>Kenneth Riles: Backend developer, Researcher, Tester</li>
                  <li>Zachary Mclaughlin: Full Stack Developer, Framework Tester.</li>
                  <li>Shadi Zgheib: Backend, ML/AI developer, Tester</li>
                </ul>
            </section>
        </div>
    </main>
  </div>
  <script>
  let slideIndex = 0;
  showSlide(slideIndex);

  function changeSlide(n) {
    showSlide(slideIndex += n);
  }

  function showSlide(n) {
    const slides = document.getElementsByClassName("slide");
    if (n >= slides.length) slideIndex = 0;
    if (n < 0) slideIndex = slides.length - 1;

    for (let slide of slides) {
      slide.style.display = "none";
    }
    slides[slideIndex].style.display = "block";
  }
</script>
</body>
</html>