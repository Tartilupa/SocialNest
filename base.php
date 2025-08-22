<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.html');
    exit();
}

$username = $_SESSION['username'];

$formulas = [
    
    // Algebra
    ["name" => "Quadratic Formula", "category" => "Algebra", "formula" => "x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}"],
    ["name" => "Linear Equation", "category" => "Algebra", "formula" => "y = mx + b"],
    ["name" => "Slope Formula", "category" => "Algebra", "formula" => "m = \\frac{y_2 - y_1}{x_2 - x_1}"],
    ["name" => "Distance Formula", "category" => "Algebra", "formula" => "d = \\sqrt{(x_2-x_1)^2 + (y_2-y_1)^2}"],
    ["name" => "Midpoint Formula", "category" => "Algebra", "formula" => "(\\frac{x_1 + x_2}{2}, \\frac{y_1 + y_2}{2})"],
    ["name" => "Point-Slope Form", "category" => "Algebra", "formula" => "y - y_1 = m(x - x_1)"],
    ["name" => "Slope-Intercept Form", "category" => "Algebra", "formula" => "y = mx + b"],
    ["name" => "Standard Form", "category" => "Algebra", "formula" => "Ax + By = C"],
    ["name" => "Factorial", "category" => "Algebra", "formula" => "n! = n × (n-1)!"],
    ["name" => "Binomial Expansion", "category" => "Algebra", "formula" => "(x + y)^n = \\sum_{k=0}^n \\binom{n}{k} x^{n-k} y^k"],

    // Geometry
    ["name" => "Pythagorean Theorem", "category" => "Geometry", "formula" => "c^2 = a^2 + b^2"],
    ["name" => "Area of Circle", "category" => "Geometry", "formula" => "A = \\pi r^2"],
    ["name" => "Circumference of Circle", "category" => "Geometry", "formula" => "C = 2\\pi r"],
    ["name" => "Area of Rectangle", "category" => "Geometry", "formula" => "A = l × w"],
    ["name" => "Area of Triangle", "category" => "Geometry", "formula" => "A = \\frac{1}{2}bh"],
    ["name" => "Area of Trapezoid", "category" => "Geometry", "formula" => "A = \\frac{1}{2}(b_1 + b_2)h"],
    ["name" => "Volume of Sphere", "category" => "Geometry", "formula" => "V = \\frac{4}{3}\\pi r^3"],
    ["name" => "Surface Area of Sphere", "category" => "Geometry", "formula" => "A = 4\\pi r^2"],
    ["name" => "Volume of Cylinder", "category" => "Geometry", "formula" => "V = \\pi r^2h"],
    ["name" => "Surface Area of Cylinder", "category" => "Geometry", "formula" => "A = 2\\pi r^2 + 2\\pi rh"],

    // Trigonometry
    ["name" => "Sine Law", "category" => "Trigonometry", "formula" => "\\frac{a}{\\sin A} = \\frac{b}{\\sin B} = \\frac{c}{\\sin C}"],
    ["name" => "Cosine Law", "category" => "Trigonometry", "formula" => "c^2 = a^2 + b^2 - 2ab\\cos C"],
    ["name" => "Tangent", "category" => "Trigonometry", "formula" => "\\tan \\theta = \\frac{\\sin \\theta}{\\cos \\theta}"],
    ["name" => "Double Angle Sine", "category" => "Trigonometry", "formula" => "\\sin 2\\theta = 2\\sin \\theta \\cos \\theta"],
    ["name" => "Double Angle Cosine", "category" => "Trigonometry", "formula" => "\\cos 2\\theta = \\cos^2 \\theta - \\sin^2 \\theta"],
    ["name" => "Half Angle Sine", "category" => "Trigonometry", "formula" => "\\sin \\frac{\\theta}{2} = \\pm \\sqrt{\\frac{1-\\cos \\theta}{2}}"],
    ["name" => "Pythagorean Identity", "category" => "Trigonometry", "formula" => "\\sin^2 \\theta + \\cos^2 \\theta = 1"],
    ["name" => "Area of Triangle (Sine)", "category" => "Trigonometry", "formula" => "A = \\frac{1}{2}ab\\sin C"],
    ["name" => "Cotangent", "category" => "Trigonometry", "formula" => "\\cot \\theta = \\frac{\\cos \\theta}{\\sin \\theta}"],
    ["name" => "Secant", "category" => "Trigonometry", "formula" => "\\sec \\theta = \\frac{1}{\\cos \\theta}"],

    // Calculus
    ["name" => "Power Rule", "category" => "Calculus", "formula" => "\\frac{d}{dx}x^n = nx^{n-1}"],
    ["name" => "Chain Rule", "category" => "Calculus", "formula" => "\\frac{d}{dx}[f(g(x))] = f'(g(x))g'(x)"],
    ["name" => "Product Rule", "category" => "Calculus", "formula" => "\\frac{d}{dx}[f(x)g(x)] = f'(x)g(x) + f(x)g'(x)"],
    ["name" => "Quotient Rule", "category" => "Calculus", "formula" => "\\frac{d}{dx}\\frac{f(x)}{g(x)} = \\frac{f'(x)g(x) - f(x)g'(x)}{[g(x)]^2}"],
    ["name" => "Integration by Parts", "category" => "Calculus", "formula" => "\\int u\\,dv = uv - \\int v\\,du"],
    ["name" => "Fundamental Theorem of Calculus", "category" => "Calculus", "formula" => "\\int_a^b f'(x)\\,dx = f(b) - f(a)"],
    ["name" => "L'Hôpital's Rule", "category" => "Calculus", "formula" => "\\lim_{x \\to a} \\frac{f(x)}{g(x)} = \\lim_{x \\to a} \\frac{f'(x)}{g'(x)}"],
    ["name" => "Taylor Series", "category" => "Calculus", "formula" => "f(x) = \\sum_{n=0}^\\infty \\frac{f^{(n)}(a)}{n!}(x-a)^n"],
    ["name" => "Mean Value Theorem", "category" => "Calculus", "formula" => "f'(c) = \\frac{f(b) - f(a)}{b - a}"],
    ["name" => "Euler's Formula", "category" => "Calculus", "formula" => "e^{ix} = \\cos x + i\\sin x"],

    // Statistics
    ["name" => "Mean", "category" => "Statistics", "formula" => "\\bar{x} = \\frac{1}{n}\\sum_{i=1}^n x_i"],
    ["name" => "Variance", "category" => "Statistics", "formula" => "\\sigma^2 = \\frac{1}{n}\\sum_{i=1}^n (x_i - \\bar{x})^2"],
    ["name" => "Standard Deviation", "category" => "Statistics", "formula" => "\\sigma = \\sqrt{\\frac{1}{n}\\sum_{i=1}^n (x_i - \\bar{x})^2}"],
    ["name" => "Correlation Coefficient", "category" => "Statistics", "formula" => "r = \\frac{\\sum(x-\\bar{x})(y-\\bar{y})}{\\sqrt{\\sum(x-\\bar{x})^2\\sum(y-\\bar{y})^2}}"],
    ["name" => "Normal Distribution", "category" => "Statistics", "formula" => "f(x) = \\frac{1}{\\sigma\\sqrt{2\\pi}}e^{-\\frac{(x-\\mu)^2}{2\\sigma^2}}"],
    ["name" => "Binomial Probability", "category" => "Statistics", "formula" => "P(X=k) = \\binom{n}{k}p^k(1-p)^{n-k}"],
    ["name" => "Confidence Interval", "category" => "Statistics", "formula" => "\\bar{x} \\pm z_{\\alpha/2}\\frac{\\sigma}{\\sqrt{n}}"],
    ["name" => "Chi-Square", "category" => "Statistics", "formula" => "\\chi^2 = \\sum\\frac{(O-E)^2}{E}"],
    ["name" => "Z-Score", "category" => "Statistics", "formula" => "z = \\frac{x-\\mu}{\\sigma}"],
    ["name" => "Sample Size", "category" => "Statistics", "formula" => "n = \\frac{z^2\\sigma^2}{E^2}"],

    // Physics
    ["name" => "Newton's Second Law", "category" => "Physics", "formula" => "F = ma"],
    ["name" => "Kinetic Energy", "category" => "Physics", "formula" => "KE = \\frac{1}{2}mv^2"],
    ["name" => "Potential Energy", "category" => "Physics", "formula" => "PE = mgh"],
    ["name" => "Einstein's Mass-Energy", "category" => "Physics", "formula" => "E = mc^2"],
    ["name" => "Universal Gravitation", "category" => "Physics", "formula" => "F = G\\frac{m_1m_2}{r^2}"],
    ["name" => "Momentum", "category" => "Physics", "formula" => "p = mv"],
    ["name" => "Work", "category" => "Physics", "formula" => "W = Fd\\cos\\theta"],
    ["name" => "Power", "category" => "Physics", "formula" => "P = \\frac{W}{t}"],
    ["name" => "Ohm's Law", "category" => "Physics", "formula" => "V = IR"],
    ["name" => "Wave Speed", "category" => "Physics", "formula" => "v = f\\lambda"],

    // Finance
    ["name" => "Compound Interest", "category" => "Finance", "formula" => "A = P(1 + r)^t"],
    ["name" => "Present Value", "category" => "Finance", "formula" => "PV = \\frac{FV}{(1 + r)^t}"],
    ["name" => "Future Value", "category" => "Finance", "formula" => "FV = PV(1 + r)^t"],
    ["name" => "Annuity Payment", "category" => "Finance", "formula" => "PMT = PV\\frac{r(1+r)^n}{(1+r)^n-1}"],
    ["name" => "Return on Investment", "category" => "Finance", "formula" => "ROI = \\frac{Gain - Cost}{Cost} × 100\\%"],
    ["name" => "Break-Even Point", "category" => "Finance", "formula" => "BEP = \\frac{Fixed\\ Costs}{Price - Variable\\ Costs}"],
    ["name" => "Net Present Value", "category" => "Finance", "formula" => "NPV = \\sum_{t=0}^n \\frac{CF_t}{(1+r)^t}"],
    ["name" => "Debt-to-Equity Ratio", "category" => "Finance", "formula" => "D/E = \\frac{Total\\ Liabilities}{Total\\ Equity}"],
    ["name" => "Price-to-Earnings Ratio", "category" => "Finance", "formula" => "P/E = \\frac{Market\\ Price\\ per\\ Share}{Earnings\\ per\\ Share}"],
    ["name" => "Dividend Yield", "category" => "Finance", "formula" => "DY = \\frac{Annual\\ Dividends\\ per\\ Share}{Price\\ per\\ Share} × 100\\%"],
        // String Theory
    ["name" => "Nambu-Goto Action", "category" => "String Theory", "formula" => "S = -T\\int d\\tau d\\sigma \\sqrt{-\\det(\\partial_aX^\\mu \\partial_bX_\\mu)}"],
    ["name" => "Polyakov Action", "category" => "String Theory", "formula" => "S = -\\frac{T}{2}\\int d\\tau d\\sigma \\sqrt{-h}h^{ab}\\partial_aX^\\mu \\partial_bX_\\mu"],
    ["name" => "Beta Function", "category" => "String Theory", "formula" => "\\beta(g) = \\frac{dg}{d\\ln\\mu}"],
    ["name" => "Central Charge", "category" => "String Theory", "formula" => "c = D - 26"],
    ["name" => "String Tension", "category" => "String Theory", "formula" => "T = \\frac{1}{2\\pi\\alpha'}"],
    ["name" => "D-brane Tension", "category" => "String Theory", "formula" => "T_p = \\frac{1}{(2\\pi)^p g_s \\alpha'^{(p+1)/2}}"],
    ["name" => "Virasoro Algebra", "category" => "String Theory", "formula" => "[L_m,L_n] = (m-n)L_{m+n} + \\frac{c}{12}m(m^2-1)\\delta_{m+n,0}"],
    ["name" => "String Length", "category" => "String Theory", "formula" => "l_s = \\sqrt{\\alpha'}"],
    ["name" => "Compactification Radius", "category" => "String Theory", "formula" => "R = \\sqrt{\\alpha'k}"],
    ["name" => "T-duality", "category" => "String Theory", "formula" => "R \\rightarrow \\frac{\\alpha'}{R}"],

    // Quantum Field Theory
    ["name" => "Path Integral", "category" => "Quantum Field Theory", "formula" => "Z = \\int D\\phi\\, e^{iS[\\phi]}"],
    ["name" => "Klein-Gordon Equation", "category" => "Quantum Field Theory", "formula" => "(\\partial_\\mu\\partial^\\mu + m^2)\\phi = 0"],
    ["name" => "Dirac Equation", "category" => "Quantum Field Theory", "formula" => "(i\\gamma^\\mu\\partial_\\mu - m)\\psi = 0"],
    ["name" => "Yang-Mills Action", "category" => "Quantum Field Theory", "formula" => "S = -\\frac{1}{4}\\int d^4x\\, F^a_{\\mu\\nu}F^{a\\mu\\nu}"],
    ["name" => "Propagator", "category" => "Quantum Field Theory", "formula" => "G(x-y) = \\int \\frac{d^4p}{(2\\pi)^4}\\frac{e^{-ip(x-y)}}{p^2-m^2+i\\epsilon}"],
    ["name" => "Beta Function QCD", "category" => "Quantum Field Theory", "formula" => "\\beta(g) = -\\frac{g^3}{16\\pi^2}(11-\\frac{2}{3}n_f)"],
    ["name" => "Callan-Symanzik Equation", "category" => "Quantum Field Theory", "formula" => "(\\frac{\\partial}{\\partial\\ln\\mu} + \\beta(g)\\frac{\\partial}{\\partial g} + n\\gamma)G^{(n)} = 0"],
    ["name" => "Ward Identity", "category" => "Quantum Field Theory", "formula" => "p^\\mu M_{\\mu} = 0"],
    ["name" => "LSZ Reduction", "category" => "Quantum Field Theory", "formula" => "\\langle p_1...p_n|S|k_1...k_m \\rangle = \\prod_i\\int d^4x_i e^{ip_ix_i}(\\Box+m^2)"],
    ["name" => "Feynman Rule Vertex", "category" => "Quantum Field Theory", "formula" => "-ig\\gamma^\\mu T^a"],

    // General Relativity
    ["name" => "Einstein Field Equations", "category" => "General Relativity", "formula" => "R_{\\mu\\nu} - \\frac{1}{2}Rg_{\\mu\\nu} + \\Lambda g_{\\mu\\nu} = \\frac{8\\pi G}{c^4}T_{\\mu\\nu}"],
    ["name" => "Schwarzschild Metric", "category" => "General Relativity", "formula" => "ds^2 = -(1-\\frac{2GM}{rc^2})c^2dt^2 + (1-\\frac{2GM}{rc^2})^{-1}dr^2 + r^2d\\Omega^2"],
    ["name" => "Kerr Metric", "category" => "General Relativity", "formula" => "ds^2 = -(1-\\frac{2GMr}{\\rho^2})dt^2 - \\frac{4GMar\\sin^2\\theta}{\\rho^2}dtd\\phi + \\frac{\\rho^2}{\\Delta}dr^2"],
    ["name" => "Christoffel Symbols", "category" => "General Relativity", "formula" => "\\Gamma^\\lambda_{\\mu\\nu} = \\frac{1}{2}g^{\\lambda\\sigma}(\\partial_\\mu g_{\\nu\\sigma} + \\partial_\\nu g_{\\mu\\sigma} - \\partial_\\sigma g_{\\mu\\nu})"],
    ["name" => "Riemann Tensor", "category" => "General Relativity", "formula" => "R^\\rho_{\\sigma\\mu\\nu} = \\partial_\\mu\\Gamma^\\rho_{\\nu\\sigma} - \\partial_\\nu\\Gamma^\\rho_{\\mu\\sigma} + \\Gamma^\\rho_{\\mu\\lambda}\\Gamma^\\lambda_{\\nu\\sigma} - \\Gamma^\\rho_{\\nu\\lambda}\\Gamma^\\lambda_{\\mu\\sigma}"],
    ["name" => "Geodesic Equation", "category" => "General Relativity", "formula" => "\\frac{d^2x^\\lambda}{d\\tau^2} + \\Gamma^\\lambda_{\\mu\\nu}\\frac{dx^\\mu}{d\\tau}\\frac{dx^\\nu}{d\\tau} = 0"],
    ["name" => "Event Horizon", "category" => "General Relativity", "formula" => "r_s = \\frac{2GM}{c^2}"],
    ["name" => "Gravitational Time Dilation", "category" => "General Relativity", "formula" => "\\frac{\\Delta t_0}{\\Delta t} = \\sqrt{1-\\frac{2GM}{rc^2}}"],
    ["name" => "Einstein-Hilbert Action", "category" => "General Relativity", "formula" => "S = \\frac{1}{16\\pi G}\\int d^4x\\sqrt{-g}R"],
    ["name" => "Gravitational Waves", "category" => "General Relativity", "formula" => "h_{\\mu\\nu} = A_{\\mu\\nu}\\cos(k_\\alpha x^\\alpha)"],

    // Quantum Gravity
    ["name" => "Wheeler-DeWitt Equation", "category" => "Quantum Gravity", "formula" => "H\\Psi[g_{ij}] = 0"],
    ["name" => "ADM Hamiltonian", "category" => "Quantum Gravity", "formula" => "H = \\int d^3x(N\\mathcal{H} + N^i\\mathcal{H}_i)"],
    ["name" => "Holographic Principle", "category" => "Quantum Gravity", "formula" => "S_{max} = \\frac{A}{4l_P^2}"],
    ["name" => "Bekenstein-Hawking Entropy", "category" => "Quantum Gravity", "formula" => "S = \\frac{kc^3A}{4\\hbar G}"],
    ["name" => "Planck Length", "category" => "Quantum Gravity", "formula" => "l_P = \\sqrt{\\frac{\\hbar G}{c^3}}"],
    ["name" => "Planck Mass", "category" => "Quantum Gravity", "formula" => "m_P = \\sqrt{\\frac{\\hbar c}{G}}"],
    ["name" => "Planck Time", "category" => "Quantum Gravity", "formula" => "t_P = \\sqrt{\\frac{\\hbar G}{c^5}}"],
    ["name" => "Hawking Temperature", "category" => "Quantum Gravity", "formula" => "T = \\frac{\\hbar c^3}{8\\pi GMk_B}"],
    ["name" => "Black Hole Area Law", "category" => "Quantum Gravity", "formula" => "\\frac{dA}{dt} \\geq 0"],
    ["name" => "Information Paradox", "category" => "Quantum Gravity", "formula" => "S_{rad} = \\frac{4\\pi GM^2}{\\hbar c}"],

    // Quantum Mechanics
    ["name" => "Schrödinger Equation", "category" => "Quantum Mechanics", "formula" => "i\\hbar\\frac{\\partial}{\\partial t}\\Psi = H\\Psi"],
    ["name" => "Heisenberg Uncertainty", "category" => "Quantum Mechanics", "formula" => "\\Delta x\\Delta p \\geq \\frac{\\hbar}{2}"],
    ["name" => "Wave Function Collapse", "category" => "Quantum Mechanics", "formula" => "|\\Psi\\rangle = \\sum_n c_n|n\\rangle"],
    ["name" => "Quantum Entanglement", "category" => "Quantum Mechanics", "formula" => "|\\Psi\\rangle = \\frac{1}{\\sqrt{2}}(|00\\rangle + |11\\rangle)"],
    ["name" => "Density Matrix", "category" => "Quantum Mechanics", "formula" => "\\rho = \\sum_i p_i|\\psi_i\\rangle\\langle\\psi_i|"],
    ["name" => "Von Neumann Entropy", "category" => "Quantum Mechanics", "formula" => "S = -Tr(\\rho\\ln\\rho)"],
    ["name" => "Bell's Inequality", "category" => "Quantum Mechanics", "formula" => "|\\langle AB\\rangle + \\langle AC\\rangle| \\leq 2"],
    ["name" => "Quantum Tunneling", "category" => "Quantum Mechanics", "formula" => "T = e^{-2\\gamma d}"],
    ["name" => "Pauli Exclusion", "category" => "Quantum Mechanics", "formula" => "\\Psi(r_1,r_2) = -\\Psi(r_2,r_1)"],
    ["name" => "Path Integral QM", "category" => "Quantum Mechanics", "formula" => "K(x_b,t_b;x_a,t_a) = \\int_{x(t_a)=x_a}^{x(t_b)=x_b} Dx(t)e^{iS[x]/\\hbar}"],

    // M-Theory
    ["name" => "M2-brane Tension", "category" => "M-Theory", "formula" => "T_{M2} = \\frac{1}{4\\pi^2l_p^3}"],
    ["name" => "M5-brane Tension", "category" => "M-Theory", "formula" => "T_{M5} = \\frac{1}{(2\\pi)^5l_p^6}"],
    ["name" => "11D SUGRA Action", "category" => "M-Theory", "formula" => "S = \\frac{1}{2\\kappa_{11}^2}\\int d^{11}x\\sqrt{-g}(R - \\frac{1}{48}F_{MNPQ}F^{MNPQ})"],
    ["name" => "Membrane Action", "category" => "M-Theory", "formula" => "S = T_{M2}\\int d^3\\sigma\\sqrt{-\\det(\\partial_iX^M\\partial_jX^N g_{MN})}"],
    ["name" => "Duality Relation", "category" => "M-Theory", "formula" => "g_s = (R_{11}/l_s)^{3/2}"],
    ["name" => "BFSS Matrix Model", "category" => "M-Theory", "formula" => "H = \\frac{1}{2R}Tr(\\Pi_i^2 - [X^i,X^j]^2)"],
    ["name" => "Membrane Vertex", "category" => "M-Theory", "formula" => "V_3 = g_s\\int d^3x\\epsilon^{ijk}Tr(\\Phi_i[\\Phi_j,\\Phi_k])"],
    ["name" => "Wrapped M2", "category" => "M-Theory", "formula" => "E = \\frac{R_1R_2}{l_p^3}"],
    ["name" => "Kaluza-Klein Modes", "category" => "M-Theory", "formula" => "m_n = \\frac{n}{R_{11}}"],
    ["name" => "M-Theory Length", "category" => "M-Theory", "formula" => "l_M = (\\frac{\\hbar^2G}{c^3})^{1/3}"]
];

if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $filteredFormulas = array_filter($formulas, function($formula) use ($search) {
        return stripos($formula['name'], $search) !== false || 
               stripos($formula['category'], $search) !== false;
    });
    echo json_encode(array_values($filteredFormulas));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Math Base</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.7/MathJax.js?config=TeX-MML-AM_CHTML" async></script>
    <style>
        :root {
            --primary-color: #4F46E5;
            --primary-light: #E0E7FF;
            --background: #F9FAFB;
            --text-primary: #111827;
            --text-secondary: #4B5563;
            --border-color: #E5E7EB;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F9FAFB 0%, #E0E7FF 100%);
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.5;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .user-info {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-top: 1rem;
        }

        .search-container {
            position: relative;
            margin-bottom: 2rem;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        #searchBox {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            font-size: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            background: white;
            transition: all 0.3s ease;
        }

        #searchBox:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .formula-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .formula-item {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .formula-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .formula-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .formula-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--primary-light);
            color: var(--primary-color);
            border-radius: 1rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .formula-content {
            background: var(--background);
            padding: 1rem;
            border-radius: 0.5rem;
            font-family: 'Courier New', monospace;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .no-results {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header {
                padding: 1.5rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .formula-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Math Base</h1>
            <p class="user-info">Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></p>
        </div>

        <div class="search-container">
            <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 17C13.4183 17 17 13.4183 17 9C17 4.58172 13.4183 1 9 1C4.58172 1 1 4.58172 1 9C1 13.4183 4.58172 17 9 17Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M19 19L14.65 14.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input type="text" id="searchBox" placeholder="Search for a formula..." oninput="searchFormulas()">
        </div>

        <div id="results"></div>
    </div>

    <script>
        let debounceTimer;

        function searchFormulas() {
            const searchQuery = document.getElementById('searchBox').value;
            const resultsContainer = document.getElementById('results');

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (searchQuery.length >= 2) {
                    resultsContainer.innerHTML = '<div class="loading">Searching formulas...</div>';

                    fetch(`<?php echo $_SERVER['PHP_SELF']; ?>?search=${encodeURIComponent(searchQuery)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.length > 0) {
                                resultsContainer.innerHTML = '<div class="formula-grid">' +
                                    data.map(formula => `
                                        <div class="formula-item">
                                            <div class="formula-name">${formula.name}</div>
                                            <div class="formula-category">${formula.category}</div>
                                            <div class="formula-content">\\(${formula.formula}\\)</div>
                                        </div>
                                    `).join('') +
                                    '</div>';
                                MathJax.Hub.Queue(["Typeset", MathJax.Hub]);
                            } else {
                                resultsContainer.innerHTML = '<div class="no-results">No formulas found matching your search.</div>';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            resultsContainer.innerHTML = '<div class="no-results">An error occurred while searching. Please try again.</div>';
                        });
                } else {
                    resultsContainer.innerHTML = '<div class="no-results">Start typing to search for formulas...</div>';
                }
            }, 300);
        }

        // Initialize the search results on page load
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('results').innerHTML = '<div class="no-results">Start typing to search for formulas...</div>';
        });
    </script>
</body>
</html>