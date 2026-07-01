import { useEffect, useMemo, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import {
  ArrowRight,
  Bell,
  Check,
  Clock3,
  ChevronDown,
  FolderKanban,
  KeyRound,
  LayoutDashboard,
  LoaderIcon,
  LogOut,
  Menu,
  MessageSquare,
  Plus,
  Search,
  Settings,
  Trash2,
  UserRound,
  Users,
  X,
  ChevronRight,
  CircleHelp,
  Crown,
  Palette,
  ShoppingBag,
} from "lucide-react";
import oracleSiteLogo from "@/assets/oracle-site-logo.png";
import { AnimatedAIChat } from "@/components/ui/animated-ai-chat";
import GlowHorizonFM from "@/components/ui/glow-horizon";
import { cn } from "@/lib/utils";

export type ActiveView =
  | "dashboard"
  | "chat"
  | "prompts"
  | "projects"
  | "history"
  | "accounts"
  | "notifications"
  | "activities"
  | "employees"
  | "password"
  | "upgrade"
  | "personalization"
  | "profile"
  | "settings"
  | "help";
const SELECTED_CHAT_KEY = "zap:selectedConversationId";
const ACTIVE_VIEW_KEY = "zap:activeView";
const THEME_COLOR_KEY = "zap:themeColor";
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? "/";

type ThemeColorId = "purple" | "red" | "yellow" | "blue" | "green" | "orange" | "pink";

const THEME_COLORS: Array<{
  id: ThemeColorId;
  label: string;
  className: string;
  swatch: string;
}> = [
  { id: "purple", label: "Ungu", className: "theme-purple", swatch: "#8b5cf6" },
  { id: "red", label: "Merah", className: "theme-red", swatch: "#ef4444" },
  { id: "yellow", label: "Kuning", className: "theme-yellow", swatch: "#eab308" },
  { id: "blue", label: "Biru", className: "theme-blue", swatch: "#3b82f6" },
  { id: "green", label: "Hijau", className: "theme-green", swatch: "#22c55e" },
  { id: "orange", label: "Orange", className: "theme-orange", swatch: "#f97316" },
  { id: "pink", label: "Pink", className: "theme-pink", swatch: "#ec4899" },
];

function initialThemeColor(): ThemeColorId {
  const saved = window.localStorage.getItem(THEME_COLOR_KEY) as ThemeColorId | null;
  return saved && THEME_COLORS.some((theme) => theme.id === saved) ? saved : "purple";
}

function App() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [isLoggingIn, setIsLoggingIn] = useState(false);
  const [isCheckingSession, setIsCheckingSession] = useState(true);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [loginError, setLoginError] = useState("");
  const [user, setUser] = useState<AuthUser | null>(null);
  const [activeView, setActiveView] = useState<ActiveView>(() => {
    const saved = window.localStorage.getItem(ACTIVE_VIEW_KEY) as ActiveView | null;
    return saved ?? "chat";
  });
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [selectedConversationId, setSelectedConversationId] = useState<number | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [prompts, setPrompts] = useState<PromptTemplate[]>([]);
  const [projects, setProjects] = useState<Project[]>([]);
  const [history, setHistory] = useState<CommandRun[]>([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [isSending, setIsSending] = useState(false);
  const [isLoadingMessages, setIsLoadingMessages] = useState(false);
  const [themeColor, setThemeColor] = useState<ThemeColorId>(initialThemeColor);
  const [personalizationOpen, setPersonalizationOpen] = useState(false);

  const selectedTheme = THEME_COLORS.find((theme) => theme.id === themeColor) ?? THEME_COLORS[0];

  useEffect(() => {
    apiGet<MeResponse>("api/auth/me")
      .then((data) => {
        setIsLoggedIn(Boolean(data.authenticated));
        setUser(data.user);
      })
      .catch(() => {
        setIsLoggedIn(false);
        setUser(null);
      })
      .finally(() => setIsCheckingSession(false));
  }, []);

  useEffect(() => {
    if (!isLoggedIn) return;
    void loadSidebarData();
  }, [isLoggedIn]);

  useEffect(() => {
    if (!isLoggedIn) return;
    window.localStorage.setItem(ACTIVE_VIEW_KEY, activeView);
  }, [activeView, isLoggedIn]);

  useEffect(() => {
    if (!isLoggedIn) return;
    const handle = window.setTimeout(() => {
      void loadConversations(searchTerm);
    }, 250);
    return () => window.clearTimeout(handle);
  }, [searchTerm, isLoggedIn]);

  useEffect(() => {
    if (!selectedConversationId) {
      setMessages([]);
      return;
    }
    window.localStorage.setItem(SELECTED_CHAT_KEY, String(selectedConversationId));
    void loadMessages(selectedConversationId);
  }, [selectedConversationId]);

  const selectedConversation = useMemo(
    () => conversations.find((conversation) => Number(conversation.id) === selectedConversationId) ?? null,
    [conversations, selectedConversationId],
  );

  const loadSidebarData = async () => {
    await Promise.all([
      loadConversations(searchTerm).catch(() => undefined),
      apiGet<PromptsResponse>("api/chat/prompts").then((data) => setPrompts(data.prompts ?? [])).catch(() => setPrompts([])),
      apiGet<ProjectsResponse>("api/chat/projects").then((data) => setProjects(data.projects ?? [])).catch(() => setProjects([])),
      apiGet<HistoryResponse>("api/chat/history").then((data) => setHistory(data.history ?? [])).catch(() => setHistory([])),
    ]);
  };

  const loadConversations = async (search = "") => {
    const data = await apiGet<ConversationsResponse>(
      `api/chat/conversations${search ? `?search=${encodeURIComponent(search)}` : ""}`,
    );
    setConversations(data.conversations);
    setSelectedConversationId((current) => {
      if (current && data.conversations.some((item) => Number(item.id) === current)) return current;
      const saved = window.localStorage.getItem(SELECTED_CHAT_KEY);
      if (saved === "draft") return null;
      const savedId = saved ? Number(saved) : 0;
      if (savedId && data.conversations.some((item) => Number(item.id) === savedId)) return savedId;
      return null;
    });
  };

  const loadMessages = async (conversationId: number) => {
    setIsLoadingMessages(true);
    try {
      const data = await apiGet<MessagesResponse>(`api/chat/messages?conversation_id=${conversationId}`);
      setMessages(data.messages);
    } finally {
      setIsLoadingMessages(false);
    }
  };

  const handleLogin = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoginError("");
    setIsLoggingIn(true);

    try {
      const data = await apiPost<LoginResponse>("api/auth/login", { username, password });
      setUser(data.user);
      setIsLoggedIn(true);
      setActiveView("chat");
      setSelectedConversationId(null);
      setMessages([]);
      window.localStorage.setItem(SELECTED_CHAT_KEY, "draft");
      window.localStorage.setItem(ACTIVE_VIEW_KEY, "chat");
    } catch (error) {
      setLoginError(error instanceof Error ? error.message : "Login gagal.");
    } finally {
      setIsLoggingIn(false);
    }
  };

  const handleLogout = async () => {
    await apiPost("api/auth/logout", {}).catch(() => undefined);
    setIsLoggedIn(false);
    setSidebarOpen(false);
    setUsername("");
    setPassword("");
    setUser(null);
    setConversations([]);
    setMessages([]);
    setSelectedConversationId(null);
    window.localStorage.removeItem(SELECTED_CHAT_KEY);
    window.localStorage.removeItem(ACTIVE_VIEW_KEY);
  };

  const handleNewChat = () => {
    if (selectedConversationId === null && messages.length === 0) {
      setActiveView("chat");
      setSidebarOpen(false);
      return;
    }

    window.localStorage.setItem(SELECTED_CHAT_KEY, "draft");
    setSelectedConversationId(null);
    setMessages([]);
    setActiveView("chat");
    setSidebarOpen(false);
  };

  const handleThemeChange = (color: ThemeColorId) => {
    setThemeColor(color);
    window.localStorage.setItem(THEME_COLOR_KEY, color);
  };

  const handleSelectConversation = (conversationId: number) => {
    window.localStorage.setItem(SELECTED_CHAT_KEY, String(conversationId));
    setSelectedConversationId(conversationId);
    setActiveView("chat");
    setSidebarOpen(false);
  };

  const handleDeleteConversation = async (conversationId: number) => {
    await apiPost("api/chat/delete", { conversation_id: conversationId });
    setConversations((current) => current.filter((item) => Number(item.id) !== conversationId));
    if (selectedConversationId === conversationId) {
      window.localStorage.setItem(SELECTED_CHAT_KEY, "draft");
      setSelectedConversationId(null);
      setMessages([]);
      setActiveView("chat");
    }
  };

  const handleSendMessage = async (content: string) => {
    if (isDeleteCurrentChatCommand(content)) {
      if (!selectedConversationId) {
        window.localStorage.setItem(SELECTED_CHAT_KEY, "draft");
        setMessages([]);
        setActiveView("chat");
        return;
      }

      setIsSending(true);
      try {
        await apiPost("api/chat/delete", { conversation_id: selectedConversationId });
        setConversations((current) => current.filter((item) => Number(item.id) !== selectedConversationId));
        window.localStorage.setItem(SELECTED_CHAT_KEY, "draft");
        setSelectedConversationId(null);
        setMessages([]);
        setActiveView("chat");
      } catch (error) {
        setMessages((current) => [
          ...current,
          {
            id: `temp-error-${Date.now()}`,
            conversation_id: selectedConversationId,
            user_id: null,
            role: "assistant",
            content: error instanceof Error ? error.message : "Riwayat chat gagal dihapus.",
            created_at: new Date().toISOString(),
          },
        ]);
      } finally {
        setIsSending(false);
      }
      return;
    }

    const tempUserMessage: ChatMessage = {
      id: `temp-user-${Date.now()}`,
      conversation_id: selectedConversationId ?? "new",
      user_id: user?.id ?? null,
      role: "user",
      content,
      created_at: new Date().toISOString(),
    };

    setMessages((current) => [...current, tempUserMessage]);

    if (isTomorrowPasswordCommand(content)) {
      setIsSending(true);
      try {
        const tomorrow = tomorrowDateString();
        const data = await apiGet<AccountsResponse>(`api/akun?q=${encodeURIComponent(tomorrow)}&limit=200&offset=0`);
        const accounts = (data.accounts ?? data.data ?? []).filter((account) => account.expired_password === tomorrow);
        setMessages((current) => [
          ...current,
          {
            id: `temp-password-exp-${Date.now()}`,
            conversation_id: selectedConversationId ?? "new",
            user_id: null,
            role: "assistant",
            content: formatTomorrowPasswordResponse(tomorrow, accounts),
            metadata_json: accountDetailsMetadata(accounts),
            created_at: new Date().toISOString(),
          },
        ]);
      } catch (error) {
        setMessages((current) => [
          ...current,
          {
            id: `temp-error-${Date.now()}`,
            conversation_id: selectedConversationId ?? "new",
            user_id: null,
            role: "assistant",
            content: error instanceof Error ? error.message : "Data expired password besok gagal dimuat.",
            created_at: new Date().toISOString(),
          },
        ]);
      } finally {
        setIsSending(false);
      }
      return;
    }

    const statusFilter = statusFilterFromMessage(content);
    if (statusFilter) {
      setIsSending(true);
      try {
        const data = await apiGet<AccountsResponse>(`api/akun?status=${encodeURIComponent(statusFilter.value)}&limit=100&offset=0`);
        const accounts = data.accounts ?? data.data ?? [];
        setMessages((current) => [
          ...current,
          {
            id: `temp-status-${Date.now()}`,
            conversation_id: selectedConversationId ?? "new",
            user_id: null,
            role: "assistant",
            content: formatStatusAccountsResponse(statusFilter.label, accounts),
            metadata_json: accountDetailsMetadata(accounts),
            created_at: new Date().toISOString(),
          },
        ]);
      } catch (error) {
        setMessages((current) => [
          ...current,
          {
            id: `temp-error-${Date.now()}`,
            conversation_id: selectedConversationId ?? "new",
            user_id: null,
            role: "assistant",
            content: error instanceof Error ? error.message : "Data akun gagal dimuat.",
            created_at: new Date().toISOString(),
          },
        ]);
      } finally {
        setIsSending(false);
      }
      return;
    }

    const availableCategory = availableCategoryFromMessage(content);
    if (availableCategory) {
      setIsSending(true);
      try {
        const data = await apiGet<AccountsResponse>(
          `api/akun?kategori=${encodeURIComponent(availableCategory.value)}&status=aktif&limit=200&offset=0`,
        );
        const accounts = data.accounts ?? data.data ?? [];
        const fallbackData = await apiGet<AccountsResponse>("api/akun?kategori=belum_terjual&status=aktif&limit=1&offset=0");
        const fallbackAccounts = fallbackData.accounts ?? fallbackData.data ?? [];
        setMessages((current) => [
          ...current,
          {
            id: `temp-available-${Date.now()}`,
            conversation_id: selectedConversationId ?? "new",
            user_id: null,
            role: "assistant",
            content: formatAvailableAccountsResponse(availableCategory.label, availableCategory.capacity, accounts, fallbackAccounts[0]),
            metadata_json: accountDetailsMetadata(
              availableAccountDetails(availableCategory.capacity, accounts, fallbackAccounts[0]),
              availableAccountCopyText(availableCategory.capacity, accounts, fallbackAccounts[0]),
            ),
            created_at: new Date().toISOString(),
          },
        ]);
      } catch (error) {
        setMessages((current) => [
          ...current,
          {
            id: `temp-error-${Date.now()}`,
            conversation_id: selectedConversationId ?? "new",
            user_id: null,
            role: "assistant",
            content: error instanceof Error ? error.message : "Data akun tersedia gagal dimuat.",
            created_at: new Date().toISOString(),
          },
        ]);
      } finally {
        setIsSending(false);
      }
      return;
    }

    setIsSending(true);
    try {
      const [data] = await Promise.all([
        apiPost<SendMessageResponse>("api/chat/send", {
          conversation_id: selectedConversationId,
          content,
        }),
        delay(1000),
      ]);

      if (data.deleted_conversation) {
        const deletedId = Number(data.deleted_conversation_id ?? selectedConversationId ?? 0);
        setConversations((current) => current.filter((item) => Number(item.id) !== deletedId));
        window.localStorage.setItem(SELECTED_CHAT_KEY, "draft");
        setSelectedConversationId(null);
        setMessages([]);
        setActiveView("chat");
        return;
      }

      if (!data.conversation || !data.user_message || !data.assistant_message) {
        throw new Error(data.message || "Respons chat tidak lengkap.");
      }

      const conversation = data.conversation;
      const userMessage = data.user_message;
      const assistantMessage = data.assistant_message;

      setSelectedConversationId(Number(conversation.id));
      window.localStorage.setItem(SELECTED_CHAT_KEY, String(conversation.id));
      setMessages((current) => [
        ...current.filter((message) => message.id !== tempUserMessage.id),
        userMessage,
        assistantMessage,
      ]);
      setConversations((current) => {
        const withoutUpdated = current.filter((item) => Number(item.id) !== Number(conversation.id));
        return [conversation, ...withoutUpdated];
      });
      if (data.command_run_id) {
        const historyData = await apiGet<HistoryResponse>("api/chat/history");
        setHistory(historyData.history);
      }
    } catch (error) {
      setMessages((current) => [
        ...current,
        {
          id: `temp-error-${Date.now()}`,
          conversation_id: selectedConversationId ?? "new",
          user_id: null,
          role: "assistant",
          content: error instanceof Error ? error.message : "Maaf, balasan gagal dimuat dari server CI3.",
          created_at: new Date().toISOString(),
        },
      ]);
    } finally {
      setIsSending(false);
    }
  };

  return (
    <main className={cn("lab-bg relative min-h-screen overflow-hidden bg-[#050507] text-white", selectedTheme.className)}>
      <div className="fixed inset-0 z-0 overflow-hidden">
        <GlowHorizonFM variant="top" />
      </div>
      <div className="pointer-events-none fixed inset-0 z-[1] bg-[radial-gradient(circle_at_center,transparent_0%,transparent_52%,rgba(0,0,0,0.45)_100%)]" />

      <AnimatePresence mode="wait">
        {isCheckingSession ? (
          <motion.section key="checking" className="relative z-10 grid min-h-screen place-items-center px-5">
            <div className="flex items-center gap-3 rounded-2xl border border-white/10 bg-black/35 px-5 py-4 text-sm text-white/60 backdrop-blur-2xl">
              <LoaderIcon className="h-4 w-4 animate-spin" />
              Checking session
            </div>
          </motion.section>
        ) : !isLoggedIn ? (
          <LoginScreen
            username={username}
            password={password}
            isLoggingIn={isLoggingIn}
            loginError={loginError}
            onUsernameChange={setUsername}
            onPasswordChange={setPassword}
            onSubmit={handleLogin}
          />
        ) : (
          <motion.section
            key="chat"
            className="relative z-10 min-h-screen"
            initial={{ opacity: 0, y: 18 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.55, ease: "easeOut" }}
          >
            <button
              type="button"
              onClick={() => setSidebarOpen(true)}
              className="fixed left-4 top-4 z-40 grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-black/45 text-white/75 backdrop-blur-xl transition hover:bg-white/10 hover:text-white md:hidden"
              aria-label="Open sidebar"
            >
              <Menu className="h-5 w-5" />
            </button>

            <Sidebar
              open={sidebarOpen}
              activeView={activeView}
              conversations={conversations}
              selectedConversationId={selectedConversationId}
              searchTerm={searchTerm}
              collapsed={sidebarCollapsed}
              onClose={() => setSidebarOpen(false)}
              onToggleCollapsed={() => setSidebarCollapsed((current) => !current)}
              onLogout={handleLogout}
              onNewChat={handleNewChat}
              onSearchChange={setSearchTerm}
              onSelectConversation={handleSelectConversation}
              onDeleteConversation={handleDeleteConversation}
              onOpenPersonalization={() => {
                setPersonalizationOpen(true);
                setSidebarOpen(false);
              }}
              onViewChange={(view) => {
                setActiveView(view);
                setSidebarOpen(false);
              }}
            />

            <div className={cn("min-h-screen transition-[padding] duration-300", sidebarCollapsed ? "md:pl-0" : "md:pl-72")}>
              <AnimatedAIChat
                activeView={activeView}
                conversation={selectedConversation}
                messages={messages}
                prompts={prompts}
                projects={projects}
                history={history}
                isSending={isSending}
                isLoadingMessages={isLoadingMessages}
                sidebarCollapsed={sidebarCollapsed}
                onSendMessage={handleSendMessage}
                onUsePrompt={(prompt) => {
                  setActiveView("chat");
                  void handleSendMessage(`${prompt.command} `);
                }}
              />
            </div>
          </motion.section>
        )}
      </AnimatePresence>

      <PersonalizationModal
        open={personalizationOpen}
        themeColor={themeColor}
        onClose={() => setPersonalizationOpen(false)}
        onSelectTheme={handleThemeChange}
      />
    </main>
  );
}

function LoginScreen({
  username,
  password,
  isLoggingIn,
  loginError,
  onUsernameChange,
  onPasswordChange,
  onSubmit,
}: {
  username: string;
  password: string;
  isLoggingIn: boolean;
  loginError: string;
  onUsernameChange: (value: string) => void;
  onPasswordChange: (value: string) => void;
  onSubmit: (event: React.FormEvent<HTMLFormElement>) => void;
}) {
  return (
    <motion.section
      key="login"
      className="relative z-10 grid min-h-screen place-items-center px-5 py-10"
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0, y: -16, scale: 0.985 }}
      transition={{ duration: 0.45 }}
    >
      <div className="w-full max-w-md pt-10 sm:pt-16">
        <motion.div className="mb-8 text-center" initial={{ opacity: 0, y: 16 }} animate={{ opacity: 1, y: 0 }}>
          <h1 className="text-4xl font-semibold leading-tight tracking-normal text-white sm:text-5xl">
            Welcome to the
            <span className="mt-2 block bg-gradient-to-r from-[#6f4aff] via-[#5b35f2] to-[#4922E5] bg-clip-text text-transparent">
              AI-Powered World
            </span>
          </h1>
          <p className="mx-auto mt-4 max-w-sm text-sm leading-6 text-white/45">
            Login terlebih dahulu untuk melanjutkan.
          </p>
        </motion.div>

        <motion.form
          onSubmit={onSubmit}
          className="relative overflow-hidden rounded-2xl border border-white/10 bg-black/35 p-5 shadow-2xl shadow-black/40 backdrop-blur-2xl"
          initial={{ opacity: 0, y: 24, scale: 0.98 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
        >
          <div className="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/10 via-transparent to-violet-600/10" />
          <div className="relative mx-auto mb-6 grid h-44 w-60 place-items-center rounded-2xl bg-black/35 shadow-2xl shadow-violet-600/20">
            <img src={oracleSiteLogo} alt="Oracle Site" className="h-40 w-56 object-contain" />
          </div>
          <div className="relative space-y-4">
            <label className="block">
              <span className="mb-2 block text-sm text-white/65">Username</span>
              <input
                value={username}
                onChange={(event) => onUsernameChange(event.target.value)}
                type="text"
                required
                placeholder="admin"
                autoComplete="username"
                className="h-12 w-full rounded-xl border border-white/10 bg-black/25 px-4 text-sm text-white outline-none transition focus:border-violet-400/70 focus:ring-4 focus:ring-violet-500/10"
              />
            </label>
            <label className="block">
              <span className="mb-2 block text-sm text-white/65">Password</span>
              <input
                value={password}
                onChange={(event) => onPasswordChange(event.target.value)}
                type="password"
                required
                minLength={6}
                placeholder="admin123"
                autoComplete="current-password"
                className="h-12 w-full rounded-xl border border-white/10 bg-black/25 px-4 text-sm text-white outline-none transition focus:border-violet-400/70 focus:ring-4 focus:ring-violet-500/10"
              />
            </label>
            {loginError && <div className="rounded-xl border border-red-400/20 bg-red-500/10 px-3 py-2 text-sm text-red-100">{loginError}</div>}
            <button type="submit" disabled={isLoggingIn} className="theme-hover-fill flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-white font-semibold text-[#09090b] shadow-lg shadow-white/10 transition hover:-translate-y-0.5 disabled:translate-y-0 disabled:cursor-wait disabled:opacity-80">
              {isLoggingIn ? <><LoaderIcon className="h-4 w-4 animate-spin" /><span>Memuat ruang chat</span></> : <><span>Masuk ke Violence AI</span><ArrowRight className="h-4 w-4" /></>}
            </button>
            <p className="text-center text-xs text-white/35">Login database: kocak / 12kali34kali</p>
          </div>
        </motion.form>
      </div>
    </motion.section>
  );
}

interface SidebarProps {
  open: boolean;
  activeView: ActiveView;
  conversations: Conversation[];
  selectedConversationId: number | null;
  searchTerm: string;
  collapsed: boolean;
  onClose: () => void;
  onToggleCollapsed: () => void;
  onLogout: () => void;
  onNewChat: () => void;
  onSearchChange: (value: string) => void;
  onSelectConversation: (id: number) => void;
  onDeleteConversation: (id: number) => void;
  onOpenPersonalization: () => void;
  onViewChange: (view: ActiveView) => void;
}

function Sidebar({
  open,
  activeView,
  conversations,
  selectedConversationId,
  searchTerm,
  collapsed,
  onClose,
  onToggleCollapsed,
  onLogout,
  onNewChat,
  onSearchChange,
  onSelectConversation,
  onDeleteConversation,
  onOpenPersonalization,
  onViewChange,
}: SidebarProps) {
  const [accountOpen, setAccountOpen] = useState(false);
  const [recentOpen, setRecentOpen] = useState(true);
  const [dashboardOpen, setDashboardOpen] = useState(true);
  const dashboardViews: ActiveView[] = ["dashboard", "accounts", "profile", "notifications", "activities", "employees", "password"];
  const closeAccountMenu = () => setAccountOpen(false);
  const navigateSidebar = (view: ActiveView) => {
    closeAccountMenu();
    onViewChange(view);
  };

  return (
    <>
      <AnimatePresence>
        {open && (
          <motion.button type="button" className="fixed inset-0 z-40 bg-black/55 backdrop-blur-sm md:hidden" aria-label="Close sidebar overlay" onClick={onClose} initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} />
        )}
      </AnimatePresence>

      <aside className={cn("fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-white/10 bg-black/45 p-5 text-white shadow-2xl shadow-black/40 backdrop-blur-2xl transition-transform duration-300", open ? "translate-x-0" : "-translate-x-full", collapsed ? "md:-translate-x-full" : "md:translate-x-0")}>
        <div className="mb-5 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div><div className="text-lg font-semibold">Violence AI</div><div className="text-sm text-white/40">Dashboard pengelolaan</div></div>
          </div>
          <button type="button" onClick={onClose} className="grid h-9 w-9 place-items-center rounded-lg text-white/45 transition hover:bg-white/10 hover:text-white md:hidden" aria-label="Close sidebar"><X className="h-4 w-4" /></button>
        </div>

        <button type="button" onClick={() => { closeAccountMenu(); onNewChat(); }} className="theme-slide-button mb-4 flex h-11 items-center justify-center gap-2 rounded-lg border border-transparent bg-white font-semibold text-[#09090b] shadow-lg shadow-white/10 transition hover:-translate-y-0.5">
          <Plus className="h-4 w-4" /> New Chat
        </button>

        <label className="mb-5 flex h-10 items-center gap-3 rounded-lg border border-white/10 bg-white/[0.03] px-3 text-sm text-white/45 transition focus-within:border-white/20">
          <Search className="h-4 w-4" />
          <input value={searchTerm} onChange={(event) => onSearchChange(event.target.value)} placeholder="Search chats" className="min-w-0 flex-1 bg-transparent text-white outline-none placeholder:text-white/35" />
        </label>

        <nav className="space-y-3">
          <button
            type="button"
            onClick={() => {
              closeAccountMenu();
              setDashboardOpen((current) => !current);
            }}
            className={cn(
              "flex h-11 w-full items-center gap-3 rounded-md px-4 text-sm font-semibold transition",
              dashboardViews.includes(activeView) ? "bg-white/[0.08] text-white shadow-inner shadow-white/5" : "text-white/55 hover:bg-white/[0.06] hover:text-white",
            )}
          >
            <LayoutDashboard className="h-4 w-4" />
            <span className="flex-1 text-left">Dashboard</span>
            <ChevronDown className={cn("h-4 w-4 transition-transform", dashboardOpen ? "rotate-180" : "rotate-0")} />
          </button>

          <AnimatePresence initial={false}>
            {dashboardOpen && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: "auto", opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                className="overflow-hidden"
              >
                <div className="space-y-2 pl-3">
                  <SidebarItem active={activeView === "accounts"} onClick={() => navigateSidebar("accounts")} icon={<FolderKanban className="h-4 w-4" />} label="Kelola Akun" />
                  <SidebarItem active={activeView === "profile"} onClick={() => navigateSidebar("profile")} icon={<UserRound className="h-4 w-4" />} label="Profile" />
                  <SidebarItem active={activeView === "notifications"} onClick={() => navigateSidebar("notifications")} icon={<Bell className="h-4 w-4" />} label="Notifikasi" />
                  <SidebarItem active={activeView === "activities"} onClick={() => navigateSidebar("activities")} icon={<Clock3 className="h-4 w-4" />} label="Aktivitas" />
                  <SidebarItem active={activeView === "employees"} onClick={() => navigateSidebar("employees")} icon={<Users className="h-4 w-4" />} label="Kepegawaian" />
                  <SidebarItem active={activeView === "password"} onClick={() => navigateSidebar("password")} icon={<KeyRound className="h-4 w-4" />} label="Ganti Password Exp" />
                </div>
              </motion.div>
            )}
          </AnimatePresence>

          <SidebarItem active={activeView === "chat"} onClick={() => navigateSidebar("chat")} icon={<MessageSquare className="h-4 w-4" />} label="Chat AI" />
        </nav>

        <div className="mt-5 min-h-0 flex-1 overflow-hidden">
          <button type="button" onClick={() => setRecentOpen((current) => !current)} className="mb-2 flex w-full items-center justify-between rounded-lg px-2 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white/35 transition hover:bg-white/[0.05]">
            <span>Recent</span>
            <ChevronDown className={cn("h-4 w-4 transition-transform", recentOpen ? "rotate-180" : "rotate-0")} />
          </button>
          <AnimatePresence initial={false}>
            {recentOpen && (
              <motion.div initial={{ height: 0, opacity: 0 }} animate={{ height: "auto", opacity: 1 }} exit={{ height: 0, opacity: 0 }} className="no-scrollbar max-h-64 space-y-1 overflow-y-auto pr-1">
            {conversations.length === 0 && <div className="px-3 py-2 text-sm text-white/35">Belum ada chat.</div>}
            {conversations.map((chat) => (
              <div key={chat.id} className={cn("group flex items-center gap-1 rounded-lg transition", Number(chat.id) === selectedConversationId ? "bg-white/[0.10]" : "hover:bg-white/[0.06]")}>
                <button
                  type="button"
                  onClick={() => {
                    closeAccountMenu();
                    onSelectConversation(Number(chat.id));
                  }}
                  className={cn(
                    "min-w-0 flex-1 truncate px-3 py-2 text-left text-sm transition",
                    Number(chat.id) === selectedConversationId ? "text-white" : "text-white/45 hover:text-white",
                  )}
                >
                  {chat.title}
                </button>
                <button
                  type="button"
                  onClick={() => {
                    closeAccountMenu();
                    void onDeleteConversation(Number(chat.id));
                  }}
                  className="mr-1 grid h-7 w-7 shrink-0 place-items-center rounded-md text-white/0 transition hover:bg-red-500/15 hover:text-red-100 group-hover:text-white/45"
                  aria-label={`Hapus ${chat.title}`}
                >
                  <Trash2 className="h-3.5 w-3.5" />
                </button>
              </div>
            ))}
              </motion.div>
            )}
          </AnimatePresence>
        </div>

        <div className="relative mt-4 border-t border-white/10 pt-4">
          <AnimatePresence>
            {accountOpen && (
              <motion.div
                initial={{ opacity: 0, y: 12, scale: 0.98 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={{ opacity: 0, y: 12, scale: 0.98 }}
                className="absolute bottom-[4.7rem] left-0 right-0 overflow-hidden rounded-2xl border border-white/10 bg-black/90 p-3 shadow-2xl shadow-black/70 backdrop-blur-2xl"
              >
                <button type="button" onClick={() => navigateSidebar("profile")} className="flex w-full items-center gap-3 rounded-xl p-2 text-left transition hover:bg-white/[0.06]">
                  <AvatarMark />
                  <div className="min-w-0 flex-1">
                    <div className="truncate text-sm font-semibold">ZAP PRO</div>
                    <div className="text-xs text-white/65">PRO</div>
                  </div>
                  <ChevronRight className="h-4 w-4 text-white/70" />
                </button>
                <div className="my-2 h-px bg-white/10" />
                <AccountMenuItem icon={<Crown className="h-4 w-4" />} label="Upgrade plan" onClick={() => navigateSidebar("upgrade")} />
                <AccountMenuItem icon={<Palette className="h-4 w-4" />} label="Personalization" onClick={() => { closeAccountMenu(); onOpenPersonalization(); }} />
                <AccountMenuItem icon={<UserRound className="h-4 w-4" />} label="Profile" onClick={() => navigateSidebar("profile")} />
                <AccountMenuItem icon={<Settings className="h-4 w-4" />} label="Settings" onClick={() => navigateSidebar("settings")} />
                <div className="my-2 h-px bg-white/10" />
                <AccountMenuItem icon={<CircleHelp className="h-4 w-4" />} label="Help" onClick={() => navigateSidebar("help")} trailing />
                <AccountMenuItem icon={<LogOut className="h-4 w-4" />} label="Log out" onClick={() => { closeAccountMenu(); onLogout(); }} />
              </motion.div>
            )}
          </AnimatePresence>
          <button type="button" onClick={() => setAccountOpen((open) => !open)} className="flex w-full items-center gap-3 rounded-xl border border-white/10 bg-black/35 p-3 text-left transition hover:bg-white/[0.06]">
            <AvatarMark />
            <div className="min-w-0 flex-1">
              <div className="truncate text-sm font-semibold">ZAP PRO</div>
              <div className="text-xs text-white/55">PRO</div>
            </div>
            <ShoppingBag className="h-4 w-4 text-white/65" />
          </button>
        </div>
      </aside>

      <div className={cn("fixed top-4 z-40 hidden transition-[left] duration-300 md:block", collapsed ? "left-4" : "left-72")}>
        <button type="button" onClick={onToggleCollapsed} className="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-black/25 text-white backdrop-blur-xl transition hover:bg-white/10" aria-label="Toggle sidebar">
          <Menu className="h-5 w-5" />
        </button>
      </div>
    </>
  );
}

function AvatarMark() {
  return <div className="grid h-9 w-9 place-items-center rounded-full bg-orange-500 text-xs font-semibold text-white">GP</div>;
}

function AccountMenuItem({ icon, label, onClick, trailing }: { icon: React.ReactNode; label: string; onClick: () => void; trailing?: boolean }) {
  return (
    <button type="button" onClick={onClick} className="flex h-9 w-full items-center gap-3 rounded-lg px-2 text-sm text-white/80 transition hover:bg-white/[0.06] hover:text-white">
      {icon}
      <span className="flex-1 text-left">{label}</span>
      {trailing && <ChevronRight className="h-4 w-4 text-white/60" />}
    </button>
  );
}

function PersonalizationModal({
  open,
  themeColor,
  onClose,
  onSelectTheme,
}: {
  open: boolean;
  themeColor: ThemeColorId;
  onClose: () => void;
  onSelectTheme: (color: ThemeColorId) => void;
}) {
  const selectedIndex = Math.max(0, THEME_COLORS.findIndex((theme) => theme.id === themeColor));
  const selectedTheme = THEME_COLORS[selectedIndex] ?? THEME_COLORS[0];

  return (
    <AnimatePresence>
      {open && (
        <>
          <motion.button
            type="button"
            className="fixed inset-0 z-[70] bg-black/65 backdrop-blur-sm"
            aria-label="Tutup personalization"
            onClick={onClose}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
          />
          <motion.section
            role="dialog"
            aria-modal="true"
            aria-labelledby="personalization-title"
            className="fixed left-1/2 top-1/2 z-[80] w-[min(92vw,30rem)] -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-white/10 bg-[#0b0b10]/95 p-5 text-white shadow-2xl shadow-black/60 backdrop-blur-2xl"
            initial={{ opacity: 0, scale: 0.96, y: 18 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.96, y: 18 }}
            transition={{ duration: 0.18 }}
          >
            <div className="mb-5 flex items-start justify-between gap-4">
              <div>
                <h2 id="personalization-title" className="text-lg font-semibold">Personalization</h2>
                <p className="mt-1 text-sm text-white/45">Pilih warna tema aplikasi.</p>
              </div>
              <button type="button" onClick={onClose} className="grid h-9 w-9 place-items-center rounded-lg text-white/55 transition hover:bg-white/10 hover:text-white" aria-label="Tutup">
                <X className="h-4 w-4" />
              </button>
            </div>

            <div className="rounded-2xl border border-white/10 bg-white/[0.035] p-2">
              <div className="relative grid h-14 grid-cols-7 gap-1 overflow-hidden rounded-xl bg-black/30 p-1">
                <motion.div
                  className="absolute bottom-1 top-1 rounded-lg border border-white/15 bg-[var(--theme-400)] shadow-lg shadow-[var(--theme-shadow)]"
                  style={{ left: 4, width: `calc((100% - 8px) / ${THEME_COLORS.length})` }}
                  animate={{ x: `${selectedIndex * 100}%` }}
                  transition={{ type: "spring", stiffness: 420, damping: 34 }}
                />

                {THEME_COLORS.map((theme) => {
                  const selected = theme.id === themeColor;
                  return (
                    <button
                      key={theme.id}
                      type="button"
                      onClick={() => onSelectTheme(theme.id)}
                      aria-pressed={selected}
                      aria-label={`Pilih tema ${theme.label}`}
                      className="relative z-10 grid min-w-0 place-items-center rounded-lg border border-transparent bg-transparent p-0 shadow-none hover:border-transparent hover:bg-transparent hover:shadow-none"
                    >
                      <span className="grid h-8 w-8 place-items-center rounded-full border border-white/20" style={{ background: theme.swatch }}>
                        {selected && <Check className="h-4 w-4 text-white drop-shadow" />}
                      </span>
                    </button>
                  );
                })}
              </div>

              <div className="mt-3 flex items-center justify-between gap-3 px-1">
                <div className="flex min-w-0 items-center gap-2">
                  <span className="h-3 w-3 shrink-0 rounded-full" style={{ background: selectedTheme.swatch }} />
                  <span className="truncate text-sm font-semibold text-white">{selectedTheme.label}</span>
                </div>
                <span className="text-xs text-white/40">Geser warna tema</span>
              </div>
            </div>
          </motion.section>
        </>
      )}
    </AnimatePresence>
  );
}

interface SidebarItemProps {
  icon: React.ReactNode;
  label: string;
  active?: boolean;
  onClick?: () => void;
}

function SidebarItem({ icon, label, active, onClick }: SidebarItemProps) {
  return (
    <button type="button" onClick={onClick} className={cn("flex h-11 w-full items-center gap-3 rounded-md px-4 text-sm font-semibold transition", active ? "bg-white/[0.08] text-white shadow-inner shadow-white/5" : "text-white/55 hover:bg-white/[0.06] hover:text-white")}>
      {icon}
      {label}
    </button>
  );
}

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: string;
  avatar_url?: string | null;
}

export interface Conversation {
  id: number | string;
  title: string;
  summary?: string | null;
  model: string;
  pinned: number | string;
  archived: number | string;
  last_message_at?: string | null;
  created_at: string;
  updated_at: string;
}

export interface ChatMessage {
  id: number | string;
  conversation_id: number | string;
  user_id?: number | string | null;
  role: "system" | "user" | "assistant" | "tool";
  content: string;
  metadata_json?: string | null;
  created_at: string;
}

export interface PromptTemplate {
  id: number | string;
  command: string;
  title: string;
  description?: string | null;
  prompt_body: string;
  icon?: string | null;
  is_public: number | string;
}

export interface Project {
  id: number | string;
  name: string;
  description?: string | null;
  color?: string | null;
  created_at: string;
  updated_at: string;
}

export interface CommandRun {
  id: number | string;
  command: string;
  input_text?: string | null;
  status: string;
  error_message?: string | null;
  created_at: string;
  finished_at?: string | null;
}

interface MeResponse { authenticated: boolean; user: AuthUser | null; }
interface LoginResponse { message?: string; user: AuthUser | null; }
interface ConversationsResponse { conversations: Conversation[]; }
interface MessagesResponse { messages: ChatMessage[]; }
interface SendMessageResponse {
  message?: string;
  conversation?: Conversation | null;
  user_message?: ChatMessage | null;
  assistant_message?: ChatMessage | null;
  command_run_id?: number | null;
  deleted_conversation?: boolean;
  deleted_conversation_id?: number | string | null;
}
interface PromptsResponse { prompts: PromptTemplate[]; }
interface ProjectsResponse { projects: Project[]; }
interface HistoryResponse { history: CommandRun[]; }
interface AccountsResponse { accounts?: StoreAccountSummary[]; data?: StoreAccountSummary[]; }

interface StoreAccountSummary {
  id_akun?: number | string;
  nama_akun?: string | null;
  kategori?: string | null;
  status?: string | null;
  username?: string | null;
  password?: string | null;
  website?: string | null;
  max_user?: number | string | null;
  expired_password?: string | null;
  note?: string | null;
}

function delay(ms: number) {
  return new Promise((resolve) => window.setTimeout(resolve, ms));
}

function isDeleteCurrentChatCommand(content: string) {
  const normalized = content
    .trim()
    .toLowerCase()
    .replace(/[.!?]+$/g, "")
    .replace(/\s+/g, " ");

  return [
    "hapus riwayat chat ini",
    "hapus chat ini",
    "hapus percakapan ini",
    "hapus conversation ini",
    "delete chat ini",
    "delete this chat",
    "bersihkan chat ini",
  ].includes(normalized);
}

function isTomorrowPasswordCommand(content: string) {
  const normalized = content
    .trim()
    .toLowerCase()
    .replace(/[.!?]+$/g, "")
    .replace(/\s+/g, " ");

  return (
    normalized === "ganti password besok"
    || normalized === "ganti password exp besok"
    || normalized === "password expired besok"
    || normalized === "expired password besok"
    || normalized === "exp password besok"
  );
}

function tomorrowDateString() {
  const date = new Date();
  date.setDate(date.getDate() + 1);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function statusFilterFromMessage(content: string): { value: string; label: string } | null {
  const normalized = content
    .trim()
    .toLowerCase()
    .replace(/[.!?]+$/g, "")
    .replace(/[_-]/g, " ")
    .replace(/\s+/g, " ");

  const statuses = [
    { value: "deactived", label: "Deactived", aliases: ["deactived", "deactivated"] },
    { value: "aktif", label: "Aktif", aliases: ["aktif", "active"] },
    { value: "verif", label: "Verif", aliases: ["verif", "verified", "terverifikasi"] },
    { value: "ban", label: "Ban", aliases: ["ban", "banned"] },
    { value: "disable_x", label: "Disable X", aliases: ["disable x", "disable_x", "disable-x"] },
    { value: "disable_email", label: "Disable Email", aliases: ["disable email", "disable_email", "disable-email"] },
    { value: "terjual", label: "Terjual", aliases: ["terjual", "sold"] },
  ];

  return statuses.find((status) => status.aliases.includes(normalized)) ?? null;
}

function formatTomorrowPasswordResponse(date: string, accounts: StoreAccountSummary[]) {
  if (accounts.length === 0) {
    return `Tidak ada akun yang expired password tanggal besok (${date}).`;
  }

  const lines = [`Jumlah akun yang expired password besok (${date}): ${accounts.length} data`];
  lines.push("");
  accounts.slice(0, 30).forEach((account, index) => {
    lines.push(`${index + 1}. ${account.nama_akun || "Akun"} | ${account.username || "-"} | ${account.kategori || "-"} | ${account.status || "-"} | expired ${account.expired_password || "-"}`);
  });

  if (accounts.length > 30) {
    lines.push("");
    lines.push(`Menampilkan 30 dari ${accounts.length} data pertama.`);
  }

  return lines.join("\n");
}

function availableCategoryFromMessage(content: string): { value: "sharing" | "private"; label: string; capacity: number } | null {
  const normalized = content
    .trim()
    .toLowerCase()
    .replace(/[.!?]+$/g, "")
    .replace(/\s+/g, " ");

  const wantsAvailable = /\b(berikan|kasih|tampilkan|lihat|cari)\b/.test(normalized)
    && /\b(akun tersedia|tersedia|available)\b/.test(normalized);

  if (!wantsAvailable && normalized !== "sharing" && normalized !== "private") return null;
  if (/\bsharing\b/.test(normalized)) return { value: "sharing", label: "Sharing", capacity: 4 };
  if (/\bprivate\b/.test(normalized)) return { value: "private", label: "Private", capacity: 1 };
  return null;
}

function formatStatusAccountsResponse(label: string, accounts: StoreAccountSummary[]) {
  if (accounts.length === 0) {
    return `Tidak ada akun dengan status ${label}.`;
  }

  const lines = [`Akun dengan status ${label}: ${accounts.length} data`, ""];
  accounts.slice(0, 30).forEach((account, index) => {
    const username = account.username || "-";
    const name = account.nama_akun || "Akun";
    const password = account.password || "-";
    const website = account.website || "-";
    const kategori = account.kategori || "-";
    const status = account.status || "-";
    const maxUser = account.max_user ?? "0";
    const expired = account.expired_password || "-";
    const note = account.note ? ` - ${account.note}` : "";
    lines.push(`${index + 1}. ${name}`);
    lines.push(`   Username: ${username}`);
    lines.push(`   Password: ${password}`);
    lines.push(`   Website: ${website}`);
    lines.push(`   Kategori: ${kategori}`);
    lines.push(`   Status: ${status}`);
    lines.push(`   Max user: ${maxUser}`);
    lines.push(`   Expired password: ${expired}${note}`);
  });

  if (accounts.length > 30) {
    lines.push("");
    lines.push(`Menampilkan 30 dari ${accounts.length} data pertama.`);
  }

  return lines.join("\n");
}

function formatAvailableAccountsResponse(
  label: string,
  capacity: number,
  accounts: StoreAccountSummary[],
  fallbackAccount?: StoreAccountSummary,
) {
  const availableAccounts = accounts
    .filter((account) => Number(account.max_user ?? 0) < capacity)
    .sort((first, second) => Number(second.max_user ?? 0) - Number(first.max_user ?? 0));

  if (availableAccounts.length === 0) {
    if (!fallbackAccount) {
      return `Tidak ada akun ${label} yang tersedia, dan tidak ada akun kategori belum terjual yang bisa diberikan.`;
    }

    const username = fallbackAccount.username || "-";
    const name = fallbackAccount.nama_akun || "Akun";
    const password = fallbackAccount.password || "-";
    const website = fallbackAccount.website || "-";
    const status = fallbackAccount.status || "-";
    const maxUser = fallbackAccount.max_user ?? "0";
    const expired = fallbackAccount.expired_password || "-";
    const note = fallbackAccount.note || "-";
    return [
      `Tidak ada akun ${label} yang tersedia.`,
      "",
      "Saya berikan 1 akun kategori Belum Terjual:",
      `1. ${name}`,
      `   Username: ${username}`,
      `   Password: ${password}`,
      `   Website: ${website}`,
      "   Kategori: belum_terjual",
      `   Status: ${status}`,
      `   Max user: ${maxUser}`,
      `   Expired password: ${expired}`,
      `   Note: ${note}`,
    ].join("\n");
  }

  const lines = [`Akun ${label} tersedia: ${availableAccounts.length} data`, ""];
  availableAccounts.slice(0, 30).forEach((account, index) => {
    const username = account.username || "-";
    const name = account.nama_akun || "Akun";
    const password = account.password || "-";
    const website = account.website || "-";
    const kategori = account.kategori || label.toLowerCase();
    const status = account.status || "aktif";
    const maxUser = Number(account.max_user ?? 0);
    const slot = Math.max(0, capacity - maxUser);
    const expired = account.expired_password || "-";
    const note = account.note || "-";
    lines.push(`${index + 1}. ${name}`);
    lines.push(`   Username: ${username}`);
    lines.push(`   Password: ${password}`);
    lines.push(`   Website: ${website}`);
    lines.push(`   Kategori: ${kategori}`);
    lines.push(`   Status: ${status}`);
    lines.push(`   Max user: ${maxUser}/${capacity}`);
    lines.push(`   Sisa slot: ${slot}`);
    lines.push(`   Expired password: ${expired}`);
    lines.push(`   Note: ${note}`);
  });

  if (availableAccounts.length > 30) {
    lines.push("");
    lines.push(`Menampilkan 30 dari ${availableAccounts.length} data pertama.`);
  }

  return lines.join("\n");
}

function availableAccountDetails(capacity: number, accounts: StoreAccountSummary[], fallbackAccount?: StoreAccountSummary) {
  const availableAccounts = accounts
    .filter((account) => Number(account.max_user ?? 0) < capacity)
    .sort((first, second) => Number(second.max_user ?? 0) - Number(first.max_user ?? 0));

  return availableAccounts.length > 0 ? availableAccounts : fallbackAccount ? [fallbackAccount] : [];
}

function accountDetailsMetadata(accounts: StoreAccountSummary[], copyText?: string | null) {
  if (accounts.length === 0 && !copyText) return null;
  return JSON.stringify({
    account_details: accounts.slice(0, 30).map((account) => ({
      id_akun: account.id_akun ?? null,
      nama_akun: account.nama_akun ?? null,
      kategori: account.kategori ?? null,
      status: account.status ?? null,
      username: account.username ?? null,
      password: account.password ?? null,
      website: account.website ?? null,
      max_user: account.max_user ?? null,
      expired_password: account.expired_password ?? null,
      note: account.note ?? null,
    })),
    copy_text: copyText ?? null,
  });
}

function availableAccountCopyText(capacity: number, accounts: StoreAccountSummary[], fallbackAccount?: StoreAccountSummary) {
  const account = availableAccountDetails(capacity, accounts, fallbackAccount)[0];
  if (!account) return null;

  return [
    `Email: ${account.username || "-"}`,
    `Password: ${account.password || "-"}`,
    "berikut cara login ke Grok menggunakan email:",
    "1. Buka website atau aplikasi Grok.",
    "2. Klik tombol Login / Sign in.",
    "3. Pilih opsi Lanjut dengan Email atau Masuk dengan Email.",
    "4. Masukkan email yang kami berikan",
    "5. Jika diminta, masukkan password yang kami kasih",
    ".",
    "WAJIB KETIK ULANG JANGAN COPY PASTE.",
    "",
    "dilarang Otak-atik Profil dan Password akun",
    "dilarang Otak-atik billing payment",
    "dilarang Mengganti Email & Password & MENDISABLE email dan X",
    "MELANGGAR? DENDA 500K + GARANSI HANGUS + AKUN DI TARIK",
  ].join("\n");
}

async function apiGet<T>(path: string): Promise<T> {
  const response = await fetch(apiUrl(path), { credentials: "include", headers: { Accept: "application/json" } });
  return handleJson<T>(response);
}

async function apiPost<T = { message?: string }>(path: string, body: unknown): Promise<T> {
  const apiPath = normalizeApiPath(path);
  const payload = apiPath === "api/login" && body && typeof body === "object"
    ? { username: (body as { username?: string }).username, password: (body as { password?: string }).password }
    : body;
  const response = await fetch(apiUrl(path), {
    method: "POST",
    credentials: "include",
    headers: { Accept: "application/json", "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  return handleJson<T>(response);
}

async function handleJson<T>(response: Response): Promise<T> {
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.message || "Request gagal.");
  return adaptApiResponse<T>(data);
}

function apiUrl(path: string) {
  const apiPath = normalizeApiPath(path);
  if (API_BASE_URL === "/" || API_BASE_URL === "") return `/${apiPath}`;
  return new URL(apiPath, API_BASE_URL).toString();
}

function normalizeApiPath(path: string) {
  const clean = path.replace(/^\/+/, "");
  if (clean === "api/auth/me") return "api/me";
  if (clean === "api/auth/login") return "api/login";
  if (clean === "api/auth/logout") return "api/logout";
  return clean;
}

function adaptApiResponse<T>(data: any): T {
  if (data && typeof data === "object" && "status" in data) {
    if ("user" in data && !("authenticated" in data)) {
      const user = normalizeUser(data.user);
      return { ...data, authenticated: Boolean(user), user } as T;
    }
    if ("data" in data && !("accounts" in data) && Array.isArray(data.data)) {
      return { ...data, accounts: data.data } as T;
    }
  }
  return data as T;
}

function normalizeUser(user: any): AuthUser | null {
  if (!user) return null;
  return {
    id: Number(user.id ?? user.id_user ?? 0),
    name: String(user.name ?? user.nama_user ?? user.username ?? "User"),
    email: String(user.email ?? user.username ?? ""),
    role: String(user.role ?? user.tipe_user ?? "user"),
    avatar_url: user.avatar_url ?? null,
  };
}

export default App;
