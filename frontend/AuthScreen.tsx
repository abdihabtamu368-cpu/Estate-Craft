import React, { useState, useRef, useEffect, MouseEvent } from 'react';

/**
 * Obsidian AuthScreen - Principal Design Technologist Caliber
 * Theme: Obsidian Dark (#08090D), Glassmorphism, Dynamic Cursor Spotlight,
 * Micro-interactions, Accessible & Mobile-First.
 */

export interface AuthScreenProps {
  onSuccess?: (user: { email: string; role: string }) => void;
  brandName?: string;
  version?: string;
}

export const AuthScreen: React.FC<AuthScreenProps> = ({
  onSuccess,
  brandName = "ESTATECRAFT",
  version = "v2.5"
}) => {
  // Tabs: 'signin' | 'signup'
  const [activeTab, setActiveTab] = useState<'signin' | 'signup'>('signin');

  // Form Fields
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [fullName, setFullName] = useState('');
  const [role, setRole] = useState<'client' | 'broker'>('client');
  const [rememberMe, setRememberMe] = useState(false);
  const [termsAccepted, setTermsAccepted] = useState(false);

  // States
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [showForgotModal, setShowForgotModal] = useState(false);
  const [toast, setToast] = useState<{ message: string; type: 'info' | 'success' | 'error' } | null>(null);

  // Card Mouse Spotlight & Tilt
  const cardRef = useRef<HTMLDivElement>(null);
  const [mousePos, setMousePos] = useState({ x: 0, y: 0 });
  const [tilt, setTilt] = useState({ rotateX: 0, rotateY: 0 });

  const triggerToast = (message: string, type: 'info' | 'success' | 'error' = 'info') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 3600);
  };

  // Card Mouse Move Handler for 3D Perspective & Spotlight Glow
  const handleMouseMove = (e: MouseEvent<HTMLDivElement>) => {
    if (!cardRef.current) return;
    const rect = cardRef.current.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    setMousePos({ x, y });

    const centerX = rect.width / 2;
    const centerY = rect.height / 2;
    const rotateX = ((y - centerY) / centerY) * -3.5;
    const rotateY = ((x - centerX) / centerX) * 3.5;
    setTilt({ rotateX, rotateY });
  };

  const handleMouseLeave = () => {
    setTilt({ rotateX: 0, rotateY: 0 });
  };

  // Password Strength Calculation
  const getPasswordStrength = (pass: string) => {
    let score = 0;
    if (pass.length >= 8) score++;
    if (/[A-Z]/.test(pass)) score++;
    if (/[0-9]/.test(pass)) score++;
    if (/[^A-Za-z0-9]/.test(pass)) score++;
    return score;
  };

  const strengthScore = getPasswordStrength(password);
  const strengthLabels = ['Weak', 'Fair', 'Good', 'Strong'];
  const strengthColors = ['#f43f5e', '#fbbf24', '#38bdf8', '#10b981'];

  // Submission
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg('');

    if (activeTab === 'signin') {
      if (!email || !password) {
        setErrorMsg('Please enter both email and password.');
        triggerToast('Missing credentials', 'error');
        return;
      }
    } else {
      if (!fullName || !email || !password || !termsAccepted) {
        setErrorMsg('Please complete all fields and accept the terms.');
        triggerToast('Please complete all required fields.', 'error');
        return;
      }
    }

    setIsLoading(true);

    setTimeout(() => {
      setIsLoading(false);
      setIsSuccess(true);
      triggerToast(activeTab === 'signin' ? 'Welcome back! Authenticated.' : 'Account created successfully!', 'success');
      onSuccess?.({ email, role });
      setTimeout(() => setIsSuccess(false), 3000);
    }, 1400);
  };

  const handleSocialAuth = (provider: string) => {
    triggerToast(`Connecting to ${provider} OAuth 2.0...`, 'info');
    setTimeout(() => {
      triggerToast(`Authenticated via ${provider}!`, 'success');
    }, 1200);
  };

  const fillDemo = () => {
    if (activeTab === 'signin') {
      setEmail('eleanor.vance@estatecraft.luxury');
      setPassword('Obsidian2026!#');
      triggerToast('Injected demo credentials for Eleanor Vance', 'info');
    } else {
      setFullName('Marcus Sterling');
      setEmail('marcus@sterling-capital.com');
      setPassword('QuantumAlpha#2026');
      setTermsAccepted(true);
      triggerToast('Injected demo registration profile', 'info');
    }
  };

  return (
    <div className="relative min-h-screen w-full bg-[#08090D] text-slate-100 flex items-center justify-center p-4 sm:p-6 overflow-hidden font-sans selection:bg-indigo-500/30 selection:text-white">
      
      {/* Background Ambient Glow Orbs */}
      <div className="fixed top-[-10%] left-[10%] w-[500px] h-[500px] rounded-full bg-gradient-to-br from-indigo-600/15 to-purple-600/5 blur-[120px] pointer-events-none" />
      <div className="fixed bottom-[-10%] right-[10%] w-[550px] h-[550px] rounded-full bg-gradient-to-tr from-cyan-600/15 via-blue-600/10 to-indigo-600/5 blur-[140px] pointer-events-none" />

      {/* Top Navbar Header */}
      <header className="fixed top-0 left-0 right-0 z-30 px-6 py-4 flex items-center justify-between border-b border-white/[0.04] backdrop-blur-md bg-[#08090D]/40">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 via-purple-500 to-cyan-400 p-[1px] shadow-[0_0_20px_rgba(99,102,241,0.4)]">
            <div className="w-full h-full bg-[#0b0e17] rounded-[7px] flex items-center justify-center">
              <svg className="w-4 h-4 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <polygon points="12 2 2 7 12 12 22 7 12 2" />
                <polyline points="2 17 12 22 22 17" />
                <polyline points="2 12 12 17 22 12" />
              </svg>
            </div>
          </div>
          <span className="font-bold tracking-tight text-white text-base">{brandName}</span>
          <span className="text-xs text-zinc-500 font-mono">{version}</span>
        </div>

        <div className="flex items-center gap-3">
          <div className="hidden sm:inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/[0.04] border border-white/[0.08] text-[11px] text-zinc-400">
            <span className="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981]" />
            Enterprise SSO Ready
          </div>
          <button
            onClick={fillDemo}
            className="text-xs font-medium px-3 py-1.5 rounded-lg bg-white/[0.06] hover:bg-white/[0.12] border border-white/[0.09] text-zinc-300 hover:text-white transition-all flex items-center gap-1.5"
          >
            <span>Fill Demo</span>
          </button>
        </div>
      </header>

      {/* Main Glassmorphic Card Container */}
      <main className="relative z-20 w-full max-w-[460px] mx-auto pt-16 pb-8" style={{ perspective: '1200px' }}>
        <div
          ref={cardRef}
          onMouseMove={handleMouseMove}
          onMouseLeave={handleMouseLeave}
          style={{
            transform: `perspective(1200px) rotateX(${tilt.rotateX.toFixed(2)}deg) rotateY(${tilt.rotateY.toFixed(2)}deg)`,
            transition: 'transform 0.15s ease-out',
            ['--mouse-x' as string]: `${mousePos.x}px`,
            ['--mouse-y' as string]: `${mousePos.y}px`
          }}
          className="relative rounded-[28px] p-6 sm:p-8 bg-[#0e1118]/80 backdrop-blur-2xl border border-white/[0.09] shadow-[0_30px_70px_-15px_rgba(0,0,0,0.8),inset_0_1px_0_rgba(255,255,255,0.12)] overflow-hidden"
        >
          {/* Card Top Brand & Heading */}
          <div className="text-center mb-6">
            <div className="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-indigo-500/10 border border-white/[0.08] shadow-[0_0_24px_rgba(99,102,241,0.25)] mb-3">
              <svg className="w-6 h-6 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z" />
                <path d="M2 17l10 5 10-5" />
                <path d="M2 12l10 5 10-5" />
              </svg>
            </div>
            <h1 className="text-2xl font-bold text-white tracking-tight">
              {activeTab === 'signin' ? 'Welcome back' : 'Create an account'}
            </h1>
            <p className="text-xs sm:text-sm text-zinc-400 mt-1">
              {activeTab === 'signin'
                ? 'Enter your credentials to access your luxury portfolio'
                : 'Join private investors and premier real estate brokers'}
            </p>
          </div>

          {/* Segmented Pill Tabs Switcher */}
          <div className="relative mb-6 p-1 rounded-xl bg-black/40 border border-white/[0.06]">
            <div
              className={`absolute top-1 bottom-1 w-[calc(50%-4px)] rounded-lg bg-gradient-to-b from-white/15 to-white/5 border border-white/10 shadow-md transition-transform duration-300 ease-out pointer-events-none ${
                activeTab === 'signin' ? 'translate-x-0' : 'translate-x-[calc(100%+4px)]'
              }`}
            />
            <div className="grid grid-cols-2 relative z-10 text-xs font-semibold text-center">
              <button
                type="button"
                onClick={() => { setActiveTab('signin'); setErrorMsg(''); }}
                className={`py-2.5 rounded-lg transition-colors ${activeTab === 'signin' ? 'text-white' : 'text-zinc-400 hover:text-zinc-200'}`}
              >
                Sign In
              </button>
              <button
                type="button"
                onClick={() => { setActiveTab('signup'); setErrorMsg(''); }}
                className={`py-2.5 rounded-lg transition-colors ${activeTab === 'signup' ? 'text-white' : 'text-zinc-400 hover:text-zinc-200'}`}
              >
                Create Account
              </button>
            </div>
          </div>

          {/* Social Auth Stack */}
          <div className="grid grid-cols-3 gap-2.5 mb-5">
            <button
              type="button"
              onClick={() => handleSocialAuth('Google')}
              className="flex items-center justify-center py-2.5 rounded-xl bg-white/[0.04] hover:bg-white/[0.08] border border-white/[0.08] hover:border-white/20 transition-all hover:-translate-y-0.5"
            >
              <svg className="w-4 h-4" viewBox="0 0 24 24">
                <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.7 14.8 1 12 1 7.5 1 3.7 3.6 1.8 7.3l3.7 2.9C6.4 7.2 8.9 5 12 5z"/>
                <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.6l3.7 2.9c2.2-2 3.7-5 3.7-8.7z"/>
                <path fill="#FBBC05" d="M5.5 14.8c-.2-.7-.4-1.5-.4-2.3 0-.8.1-1.6.4-2.3L1.8 7.3C.7 9.5 0 11.9 0 14.5s.7 5 1.8 7.2l3.7-2.9z"/>
                <path fill="#34A853" d="M12 23.5c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3.1 0-5.6-2.2-6.5-5.2L1.8 16.5C3.7 20.3 7.5 23.5 12 23.5z"/>
              </svg>
            </button>
            <button
              type="button"
              onClick={() => handleSocialAuth('GitHub')}
              className="flex items-center justify-center py-2.5 rounded-xl bg-white/[0.04] hover:bg-white/[0.08] border border-white/[0.08] hover:border-white/20 transition-all hover:-translate-y-0.5 text-white"
            >
              <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24">
                <path fillRule="evenodd" clipRule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
              </svg>
            </button>
            <button
              type="button"
              onClick={() => handleSocialAuth('Apple')}
              className="flex items-center justify-center py-2.5 rounded-xl bg-white/[0.04] hover:bg-white/[0.08] border border-white/[0.08] hover:border-white/20 transition-all hover:-translate-y-0.5 text-white"
            >
              <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24">
                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.63-.78 1.06-1.85.94-2.94-1 .04-2.17.67-2.82 1.44-.57.67-.99 1.74-.86 2.8 1.11.08 2.11-.52 2.74-1.3z"/>
              </svg>
            </button>
          </div>

          <div className="relative flex items-center justify-center my-4">
            <div className="w-full border-t border-white/[0.07]" />
            <span className="absolute px-3 text-[11px] uppercase tracking-wider font-semibold text-zinc-500 bg-[#0e1118]">
              or with email
            </span>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-4">
            {errorMsg && (
              <div className="p-2.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-xs text-rose-300">
                {errorMsg}
              </div>
            )}

            {/* Signup Only: Name */}
            {activeTab === 'signup' && (
              <div className="space-y-1.5">
                <label className="block text-xs font-medium text-zinc-300">Full Name</label>
                <div className="flex items-center px-3.5 py-2.5 rounded-xl bg-white/[0.04] border border-white/[0.08] focus-within:border-indigo-400/80 focus-within:bg-white/[0.06] transition-all">
                  <input
                    type="text"
                    value={fullName}
                    onChange={(e) => setFullName(e.target.value)}
                    placeholder="Eleanor Vance"
                    className="w-full bg-transparent text-sm text-white placeholder-zinc-500 focus:outline-none"
                    required
                  />
                </div>
              </div>
            )}

            {/* Email Field */}
            <div className="space-y-1.5">
              <label className="block text-xs font-medium text-zinc-300">Email Address</label>
              <div className="flex items-center px-3.5 py-2.5 rounded-xl bg-white/[0.04] border border-white/[0.08] focus-within:border-indigo-400/80 focus-within:bg-white/[0.06] transition-all">
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="name@company.com"
                  className="w-full bg-transparent text-sm text-white placeholder-zinc-500 focus:outline-none"
                  required
                />
              </div>
            </div>

            {/* Password Field */}
            <div className="space-y-1.5">
              <div className="flex items-center justify-between">
                <label className="block text-xs font-medium text-zinc-300">Password</label>
                {activeTab === 'signin' && (
                  <button
                    type="button"
                    onClick={() => setShowForgotModal(true)}
                    className="text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                  >
                    Forgot password?
                  </button>
                )}
              </div>
              <div className="flex items-center px-3.5 py-2.5 rounded-xl bg-white/[0.04] border border-white/[0.08] focus-within:border-indigo-400/80 focus-within:bg-white/[0.06] transition-all">
                <input
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full bg-transparent text-sm text-white placeholder-zinc-500 focus:outline-none tracking-wide"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="text-zinc-500 hover:text-zinc-300 transition-colors ml-2"
                >
                  {showPassword ? '🙈' : '👁️'}
                </button>
              </div>

              {/* Password Strength in Signup */}
              {activeTab === 'signup' && password.length > 0 && (
                <div className="pt-1.5 space-y-1">
                  <div className="grid grid-cols-4 gap-1.5">
                    {[0, 1, 2, 3].map((index) => (
                      <div
                        key={index}
                        className="h-1 rounded-full transition-all duration-300"
                        style={{
                          backgroundColor:
                            index < strengthScore ? strengthColors[strengthScore - 1] : 'rgba(255,255,255,0.1)'
                        }}
                      />
                    ))}
                  </div>
                  <div className="flex justify-between text-[10px] text-zinc-400">
                    <span>{strengthLabels[strengthScore - 1] || 'Weak'} password</span>
                    <span>{strengthScore}/4 criteria</span>
                  </div>
                </div>
              )}
            </div>

            {/* Checkbox Options */}
            {activeTab === 'signin' ? (
              <label className="flex items-center gap-2 cursor-pointer select-none">
                <input
                  type="checkbox"
                  checked={rememberMe}
                  onChange={(e) => setRememberMe(e.target.checked)}
                  className="w-4 h-4 rounded border-white/20 bg-black/40 text-indigo-600 focus:ring-0"
                />
                <span className="text-xs text-zinc-400">Remember me for 30 days</span>
              </label>
            ) : (
              <label className="flex items-start gap-2 cursor-pointer select-none">
                <input
                  type="checkbox"
                  checked={termsAccepted}
                  onChange={(e) => setTermsAccepted(e.target.checked)}
                  className="w-4 h-4 mt-0.5 rounded border-white/20 bg-black/40 text-indigo-600 focus:ring-0"
                  required
                />
                <span className="text-xs text-zinc-400 leading-snug">
                  I accept the Terms of Service & Privacy Protocol.
                </span>
              </label>
            )}

            {/* Primary CTA */}
            <button
              type="submit"
              disabled={isLoading}
              className={`w-full py-3 px-4 rounded-xl font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2 ${
                isSuccess
                  ? 'bg-emerald-500 text-white'
                  : 'bg-gradient-to-r from-white to-slate-200 text-black hover:shadow-[0_10px_25px_-5px_rgba(255,255,255,0.3)] active:scale-[0.98]'
              }`}
            >
              {isLoading ? (
                <span>Authenticating...</span>
              ) : isSuccess ? (
                <span>✓ Success! Welcome</span>
              ) : (
                <>
                  <span>{activeTab === 'signin' ? 'Sign In to Dashboard' : 'Create Free Account'}</span>
                  <span>&rarr;</span>
                </>
              )}
            </button>
          </form>

          {/* Subfooter */}
          <div className="mt-6 pt-5 border-t border-white/[0.06] flex items-center justify-between text-xs text-zinc-500">
            <span className="flex items-center gap-1.5">
              <span className="text-cyan-400">⚡</span> FIDO2 Passkey Support
            </span>
            <span className="text-zinc-400">256-bit SSL Encrypted</span>
          </div>
        </div>
      </main>

      {/* Forgot Password Modal */}
      {showForgotModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="rounded-2xl w-full max-w-md p-6 bg-[#0e1118] border border-white/10 shadow-2xl space-y-4">
            <div className="flex justify-between items-center">
              <h3 className="font-bold text-white text-base">Reset Password</h3>
              <button onClick={() => setShowForgotModal(false)} className="text-zinc-400 hover:text-white">&times;</button>
            </div>
            <p className="text-xs text-zinc-400">
              We will send a magic recovery token to your registered email address.
            </p>
            <input
              type="email"
              placeholder="you@domain.com"
              className="w-full px-3.5 py-2.5 rounded-xl bg-white/[0.05] border border-white/10 text-white text-sm focus:outline-none"
            />
            <div className="flex gap-2">
              <button
                onClick={() => setShowForgotModal(false)}
                className="w-1/2 py-2 rounded-xl border border-white/10 text-xs text-zinc-300"
              >
                Cancel
              </button>
              <button
                onClick={() => {
                  setShowForgotModal(false);
                  triggerToast('Magic link dispatched to your email inbox', 'success');
                }}
                className="w-1/2 py-2 rounded-xl bg-white text-black font-semibold text-xs"
              >
                Send Link
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Toast */}
      {toast && (
        <div className="fixed top-6 right-6 z-50 px-4 py-3 rounded-xl bg-[#0e1118]/95 border border-white/10 shadow-2xl flex items-center gap-2 text-xs text-white">
          <span>{toast.type === 'success' ? '✓' : toast.type === 'error' ? '✕' : 'ℹ'}</span>
          <span>{toast.message}</span>
        </div>
      )}
    </div>
  );
};

export default AuthScreen;
