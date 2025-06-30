// EMERGENCY LUCIDE REACT STUB - PREVENTS ALL LUCIDE IMPORTS
// This file replaces lucide-react to prevent className.includes errors

import React from 'react';

interface SafeIconProps {
  className?: string | undefined | null;
  size?: number;
  [key: string]: any;
}

const createStubIcon = (emoji: string, name: string) => {
  const StubIcon: React.FC<SafeIconProps> = ({ className, size = 16, ...props }) => {
    const safeClassName = typeof className === 'string' ? className : '';
    return (
      <span
        className={safeClassName}
        style={{
          fontSize: `${size}px`,
          lineHeight: 1,
          display: 'inline-block',
          verticalAlign: 'middle'
        }}
        role="img"
        aria-label={name}
        {...props}
      >
        {emoji}
      </span>
    );
  };
  StubIcon.displayName = `Stub${name}`;
  return StubIcon;
};

// Export ALL possible Lucide icons as safe stubs
export const AlertTriangle = createStubIcon('⚠️', 'AlertTriangle');
export const AlertCircle = createStubIcon('⚠️', 'AlertCircle');
export const Activity = createStubIcon('📊', 'Activity');
export const Award = createStubIcon('🏆', 'Award');
export const ArrowLeft = createStubIcon('←', 'ArrowLeft');
export const ArrowRight = createStubIcon('→', 'ArrowRight');
export const ArrowUp = createStubIcon('↑', 'ArrowUp');
export const ArrowDown = createStubIcon('↓', 'ArrowDown');
export const BarChart3 = createStubIcon('📊', 'BarChart3');
export const Bell = createStubIcon('🔔', 'Bell');
export const BellOff = createStubIcon('🔕', 'BellOff');
export const Briefcase = createStubIcon('💼', 'Briefcase');
export const Calendar = createStubIcon('📅', 'Calendar');
export const Camera = createStubIcon('📷', 'Camera');
export const Check = createStubIcon('✓', 'Check');
export const CheckCircle = createStubIcon('✅', 'CheckCircle');
export const ChevronDown = createStubIcon('▼', 'ChevronDown');
export const ChevronUp = createStubIcon('▲', 'ChevronUp');
export const ChevronLeft = createStubIcon('◀', 'ChevronLeft');
export const ChevronRight = createStubIcon('▶', 'ChevronRight');
export const Circle = createStubIcon('⭕', 'Circle');
export const Clock = createStubIcon('🕐', 'Clock');
export const Cloud = createStubIcon('☁️', 'Cloud');
export const Construction = createStubIcon('🚧', 'Construction');
export const Copy = createStubIcon('📋', 'Copy');
export const CreditCard = createStubIcon('💳', 'CreditCard');
export const Crown = createStubIcon('👑', 'Crown');
export const Database = createStubIcon('🗄️', 'Database');
export const Diamond = createStubIcon('💎', 'Diamond');
export const DollarSign = createStubIcon('💲', 'DollarSign');
export const Dot = createStubIcon('•', 'Dot');
export const Download = createStubIcon('📥', 'Download');
export const Edit = createStubIcon('✏️', 'Edit');
export const ExternalLink = createStubIcon('🔗', 'ExternalLink');
export const Eye = createStubIcon('👁️', 'Eye');
export const EyeOff = createStubIcon('🙈', 'EyeOff');
export const Factory = createStubIcon('🏭', 'Factory');
export const FileText = createStubIcon('📄', 'FileText');
export const Filter = createStubIcon('🔽', 'Filter');
export const Flag = createStubIcon('🚩', 'Flag');
export const Flower = createStubIcon('🌸', 'Flower');
export const Gem = createStubIcon('💎', 'Gem');
export const Gift = createStubIcon('🎁', 'Gift');
export const Globe = createStubIcon('🌐', 'Globe');
export const GripVertical = createStubIcon('⋮⋮', 'GripVertical');
export const HardHat = createStubIcon('⛑️', 'HardHat');
export const Hash = createStubIcon('#', 'Hash');
export const Heart = createStubIcon('❤️', 'Heart');
export const Hexagon = createStubIcon('⬡', 'Hexagon');
export const History = createStubIcon('📜', 'History');
export const Home = createStubIcon('🏠', 'Home');
export const Image = createStubIcon('🖼️', 'Image');
export const Info = createStubIcon('ℹ️', 'Info');
export const Key = createStubIcon('🔑', 'Key');
export const LogIn = createStubIcon('🔑', 'LogIn');
export const Building2 = createStubIcon('🏢', 'Building2');
export const ArrowUpRight = createStubIcon('↗️', 'ArrowUpRight');
export const ArrowDownLeft = createStubIcon('↙️', 'ArrowDownLeft');
export const Save = createStubIcon('💾', 'Save');
export const Network = createStubIcon('🌐', 'Network');
export const TrendingDown = createStubIcon('📉', 'TrendingDown');
export const MemoryStick = createStubIcon('💾', 'MemoryStick');
export const Share2 = createStubIcon('📤', 'Share2');
export const PlayCircle = createStubIcon('▶️', 'PlayCircle');
export const Facebook = createStubIcon('📘', 'Facebook');
export const Twitter = createStubIcon('🐦', 'Twitter');
export const Linkedin = createStubIcon('💼', 'Linkedin');
export const Minimize2 = createStubIcon('🔽', 'Minimize2');
export const Maximize2 = createStubIcon('🔼', 'Maximize2');
export const StarOff = createStubIcon('⭐', 'StarOff');
export const CheckCircle2 = createStubIcon('✅', 'CheckCircle2');
export const Timer = createStubIcon('⏱️', 'Timer');
export const Sparkles = createStubIcon('✨', 'Sparkles');
export const QrCode = createStubIcon('📱', 'QrCode');
export const RotateCcw = createStubIcon('🔄', 'RotateCcw');
export const Grid3X3 = createStubIcon('⚏', 'Grid3X3');
export const PieChart = createStubIcon('📊', 'PieChart');
export const HelpCircle = createStubIcon('❓', 'HelpCircle');
export const TestTube = createStubIcon('🧪', 'TestTube');
export const FileCheck = createStubIcon('📋', 'FileCheck');
export const SlidersHorizontal = createStubIcon('🎛️', 'SlidersHorizontal');
export const Coins = createStubIcon('🪙', 'Coins');
export const Wand2 = createStubIcon('🪄', 'Wand2');
export const Leaf = createStubIcon('🍃', 'Leaf');
export const Link = createStubIcon('🔗', 'Link');
export const Loader2 = createStubIcon('⏳', 'Loader2');
export const Lock = createStubIcon('🔒', 'Lock');
export const Mail = createStubIcon('📧', 'Mail');
export const MapPin = createStubIcon('📍', 'MapPin');
export const Medal = createStubIcon('🏅', 'Medal');
export const Menu = createStubIcon('☰', 'Menu');
export const MessageCircle = createStubIcon('💬', 'MessageCircle');
export const MessageSquare = createStubIcon('💬', 'MessageSquare');
export const Minus = createStubIcon('-', 'Minus');
export const Moon = createStubIcon('🌙', 'Moon');
export const Octagon = createStubIcon('⬢', 'Octagon');
export const Package = createStubIcon('📦', 'Package');
export const Palette = createStubIcon('🎨', 'Palette');
export const PanelLeft = createStubIcon('☰', 'PanelLeft');
export const Percent = createStubIcon('%', 'Percent');
export const Phone = createStubIcon('📞', 'Phone');
export const Pickaxe = createStubIcon('⛏️', 'Pickaxe');
export const Plus = createStubIcon('+', 'Plus');
export const RefreshCw = createStubIcon('🔄', 'RefreshCw');
export const Reply = createStubIcon('↩️', 'Reply');
export const Search = createStubIcon('🔍', 'Search');
export const Send = createStubIcon('📤', 'Send');
export const Settings = createStubIcon('⚙️', 'Settings');
export const Shield = createStubIcon('🛡️', 'Shield');
export const ShieldAlert = createStubIcon('🛡️⚠️', 'ShieldAlert');
export const ShieldCheck = createStubIcon('🛡️✅', 'ShieldCheck');
export const ShieldX = createStubIcon('🛡️❌', 'ShieldX');
export const ShoppingCart = createStubIcon('🛒', 'ShoppingCart');
export const Square = createStubIcon('⬜', 'Square');
export const Star = createStubIcon('⭐', 'Star');
export const Sun = createStubIcon('☀️', 'Sun');
export const Target = createStubIcon('🎯', 'Target');
export const Trash = createStubIcon('🗑️', 'Trash');
export const Trash2 = createStubIcon('🗑️', 'Trash2');
export const TrendingUp = createStubIcon('📈', 'TrendingUp');
export const Triangle = createStubIcon('🔺', 'Triangle');
export const Trophy = createStubIcon('🏆', 'Trophy');
export const Truck = createStubIcon('🚚', 'Truck');
export const Unlock = createStubIcon('🔓', 'Unlock');
export const Upload = createStubIcon('📤', 'Upload');
export const User = createStubIcon('👤', 'User');
export const UserCheck = createStubIcon('👤✅', 'UserCheck');
export const UserX = createStubIcon('👤❌', 'UserX');
export const Users = createStubIcon('👥', 'Users');
export const Video = createStubIcon('🎥', 'Video');
export const Wallet = createStubIcon('👛', 'Wallet');
export const X = createStubIcon('✕', 'X');
export const XCircle = createStubIcon('❌', 'XCircle');
export const Zap = createStubIcon('⚡', 'Zap');

// Calculator icon
export const Calculator = createStubIcon('🧮', 'Calculator');

// Add missing icons that are causing import errors
export const Bug = createStubIcon('🐛', 'Bug');
export const Code = createStubIcon('💻', 'Code');
export const Terminal = createStubIcon('⌨️', 'Terminal');
export const Cpu = createStubIcon('🖥️', 'Cpu');
export const HardDrive = createStubIcon('💾', 'HardDrive');
export const Monitor = createStubIcon('🖥️', 'Monitor');
export const Smartphone = createStubIcon('📱', 'Smartphone');
export const Tablet = createStubIcon('📱', 'Tablet');
export const Laptop = createStubIcon('💻', 'Laptop');
export const Printer = createStubIcon('🖨️', 'Printer');
export const Scanner = createStubIcon('📠', 'Scanner');
export const Headphones = createStubIcon('🎧', 'Headphones');
export const Mic = createStubIcon('🎤', 'Mic');
export const MicOff = createStubIcon('🎤❌', 'MicOff');
export const Volume = createStubIcon('🔊', 'Volume');
export const VolumeOff = createStubIcon('🔇', 'VolumeOff');
export const Play = createStubIcon('▶️', 'Play');
export const Pause = createStubIcon('⏸️', 'Pause');
export const Stop = createStubIcon('⏹️', 'Stop');
export const SkipForward = createStubIcon('⏭️', 'SkipForward');
export const SkipBack = createStubIcon('⏮️', 'SkipBack');
export const FastForward = createStubIcon('⏩', 'FastForward');
export const Rewind = createStubIcon('⏪', 'Rewind');
export const Repeat = createStubIcon('🔁', 'Repeat');
export const Shuffle = createStubIcon('🔀', 'Shuffle');
export const Bookmark = createStubIcon('🔖', 'Bookmark');
export const Tag = createStubIcon('🏷️', 'Tag');
export const Inbox = createStubIcon('📥', 'Inbox');
export const Archive = createStubIcon('📦', 'Archive');
export const Folder = createStubIcon('📁', 'Folder');
export const FolderOpen = createStubIcon('📂', 'FolderOpen');
export const File = createStubIcon('📄', 'File');
export const FileImage = createStubIcon('🖼️', 'FileImage');
export const FileVideo = createStubIcon('🎥', 'FileVideo');
export const FileAudio = createStubIcon('🎵', 'FileAudio');
export const FilePdf = createStubIcon('📄', 'FilePdf');
export const FileSpreadsheet = createStubIcon('📊', 'FileSpreadsheet');
export const FileCode = createStubIcon('📝', 'FileCode');
export const Wifi = createStubIcon('📶', 'Wifi');
export const WifiOff = createStubIcon('📶❌', 'WifiOff');
export const Bluetooth = createStubIcon('📶', 'Bluetooth');
export const Battery = createStubIcon('🔋', 'Battery');
export const BatteryLow = createStubIcon('🪫', 'BatteryLow');
export const Power = createStubIcon('⚡', 'Power');
export const PowerOff = createStubIcon('⚡❌', 'PowerOff');
export const CameraOff = createStubIcon('📷❌', 'CameraOff');
export const Gamepad = createStubIcon('🎮', 'Gamepad');
export const Joystick = createStubIcon('🕹️', 'Joystick');
export const Dice = createStubIcon('🎲', 'Dice');
export const Puzzle = createStubIcon('🧩', 'Puzzle');
export const Building = createStubIcon('🏢', 'Building');
export const Bank = createStubIcon('🏦', 'Bank');
export const Hospital = createStubIcon('🏥', 'Hospital');
export const School = createStubIcon('🏫', 'School');
export const University = createStubIcon('🏛️', 'University');
export const Church = createStubIcon('⛪', 'Church');
export const Mosque = createStubIcon('🕌', 'Mosque');
export const Temple = createStubIcon('🛕', 'Temple');
export const Synagogue = createStubIcon('🕍', 'Synagogue');
export const Warehouse = createStubIcon('🏬', 'Warehouse');
export const Office = createStubIcon('🏢', 'Office');
export const Apartment = createStubIcon('🏠', 'Apartment');
export const House = createStubIcon('🏡', 'House');
export const Tent = createStubIcon('⛺', 'Tent');
export const Castle = createStubIcon('🏰', 'Castle');
export const Stadium = createStubIcon('🏟️', 'Stadium');
export const Gym = createStubIcon('🏋️', 'Gym');
export const Pool = createStubIcon('🏊', 'Pool');
export const Beach = createStubIcon('🏖️', 'Beach');
export const Mountain = createStubIcon('⛰️', 'Mountain');
export const Volcano = createStubIcon('🌋', 'Volcano');
export const Desert = createStubIcon('🏜️', 'Desert');
export const Forest = createStubIcon('🌲', 'Forest');
export const Park = createStubIcon('🏞️', 'Park');
export const Garden = createStubIcon('🏡', 'Garden');
export const Farm = createStubIcon('🚜', 'Farm');
export const Field = createStubIcon('🌾', 'Field');
export const Orchard = createStubIcon('🍎', 'Orchard');
export const Vineyard = createStubIcon('🍇', 'Vineyard');
export const Greenhouse = createStubIcon('🌱', 'Greenhouse');
export const Barn = createStubIcon('🏚️', 'Barn');
export const Silo = createStubIcon('🏗️', 'Silo');
export const Windmill = createStubIcon('🌪️', 'Windmill');
export const Lighthouse = createStubIcon('🗼', 'Lighthouse');
export const Bridge = createStubIcon('🌉', 'Bridge');
export const Tunnel = createStubIcon('🚇', 'Tunnel');
export const Road = createStubIcon('🛣️', 'Road');
export const Highway = createStubIcon('🛣️', 'Highway');
export const Railway = createStubIcon('🛤️', 'Railway');
export const Airport = createStubIcon('✈️', 'Airport');
export const Seaport = createStubIcon('⚓', 'Seaport');
export const Harbor = createStubIcon('🚢', 'Harbor');
export const Marina = createStubIcon('⛵', 'Marina');
export const Dock = createStubIcon('🚢', 'Dock');
export const Pier = createStubIcon('🌊', 'Pier');
export const Jetty = createStubIcon('🌊', 'Jetty');
export const Wharf = createStubIcon('🚢', 'Wharf');
export const Quay = createStubIcon('🚢', 'Quay');
export const Berth = createStubIcon('⚓', 'Berth');
export const Anchorage = createStubIcon('⚓', 'Anchorage');
export const Mooring = createStubIcon('⚓', 'Mooring');
export const Buoy = createStubIcon('🌊', 'Buoy');
export const Beacon = createStubIcon('🔦', 'Beacon');
export const Signal = createStubIcon('🚦', 'Signal');
export const TrafficLight = createStubIcon('🚦', 'TrafficLight');
export const StopSign = createStubIcon('🛑', 'StopSign');
export const YieldSign = createStubIcon('⚠️', 'YieldSign');
export const SpeedLimit = createStubIcon('🚫', 'SpeedLimit');
export const NoEntry = createStubIcon('⛔', 'NoEntry');
export const Prohibited = createStubIcon('🚫', 'Prohibited');
export const Restricted = createStubIcon('🚫', 'Restricted');
export const Forbidden = createStubIcon('🚫', 'Forbidden');
export const Banned = createStubIcon('🚫', 'Banned');
export const Blocked = createStubIcon('🚫', 'Blocked');
export const Denied = createStubIcon('🚫', 'Denied');
export const Rejected = createStubIcon('❌', 'Rejected');
export const Declined = createStubIcon('❌', 'Declined');
export const Refused = createStubIcon('❌', 'Refused');
export const Cancelled = createStubIcon('❌', 'Cancelled');
export const Terminated = createStubIcon('❌', 'Terminated');
export const Ended = createStubIcon('❌', 'Ended');
export const Finished = createStubIcon('✅', 'Finished');
export const Completed = createStubIcon('✅', 'Completed');
export const Done = createStubIcon('✅', 'Done');
export const Approved = createStubIcon('✅', 'Approved');
export const Accepted = createStubIcon('✅', 'Accepted');
export const Confirmed = createStubIcon('✅', 'Confirmed');
export const Verified = createStubIcon('✅', 'Verified');
export const Validated = createStubIcon('✅', 'Validated');
export const Authenticated = createStubIcon('✅', 'Authenticated');
export const Authorized = createStubIcon('✅', 'Authorized');
export const Permitted = createStubIcon('✅', 'Permitted');
export const Allowed = createStubIcon('✅', 'Allowed');
export const Granted = createStubIcon('✅', 'Granted');
export const Enabled = createStubIcon('✅', 'Enabled');
export const Activated = createStubIcon('✅', 'Activated');
export const Started = createStubIcon('▶️', 'Started');
export const Initiated = createStubIcon('▶️', 'Initiated');
export const Launched = createStubIcon('🚀', 'Launched');
export const Deployed = createStubIcon('🚀', 'Deployed');
export const Released = createStubIcon('🚀', 'Released');
export const Published = createStubIcon('📢', 'Published');
export const Announced = createStubIcon('📢', 'Announced');
export const Broadcast = createStubIcon('📡', 'Broadcast');
export const Transmitted = createStubIcon('📡', 'Transmitted');
export const Sent = createStubIcon('📤', 'Sent');
export const Delivered = createStubIcon('📦', 'Delivered');
export const Received = createStubIcon('📥', 'Received');
export const Collected = createStubIcon('📥', 'Collected');
export const Gathered = createStubIcon('📥', 'Gathered');
export const Assembled = createStubIcon('🔧', 'Assembled');
export const Built = createStubIcon('🔨', 'Built');
export const Constructed = createStubIcon('🏗️', 'Constructed');
export const Created = createStubIcon('✨', 'Created');
export const Generated = createStubIcon('✨', 'Generated');
export const Produced = createStubIcon('🏭', 'Produced');
export const Manufactured = createStubIcon('🏭', 'Manufactured');
export const Fabricated = createStubIcon('🔧', 'Fabricated');
export const Crafted = createStubIcon('🎨', 'Crafted');
export const Designed = createStubIcon('🎨', 'Designed');
export const Developed = createStubIcon('💻', 'Developed');
export const Programmed = createStubIcon('💻', 'Programmed');
export const Coded = createStubIcon('💻', 'Coded');
export const Scripted = createStubIcon('📝', 'Scripted');
export const Written = createStubIcon('✍️', 'Written');
export const Authored = createStubIcon('✍️', 'Authored');
export const Composed = createStubIcon('🎼', 'Composed');
export const Arranged = createStubIcon('🎼', 'Arranged');
export const Orchestrated = createStubIcon('🎼', 'Orchestrated');
export const Conducted = createStubIcon('🎼', 'Conducted');
export const Directed = createStubIcon('🎬', 'Directed');
export const Managed = createStubIcon('👔', 'Managed');
export const Supervised = createStubIcon('👔', 'Supervised');
export const Overseen = createStubIcon('👁️', 'Overseen');
export const Monitored = createStubIcon('👁️', 'Monitored');
export const Watched = createStubIcon('👁️', 'Watched');
export const Observed = createStubIcon('👁️', 'Observed');
export const Inspected = createStubIcon('🔍', 'Inspected');
export const Examined = createStubIcon('🔍', 'Examined');
export const Analyzed = createStubIcon('📊', 'Analyzed');
export const Evaluated = createStubIcon('📊', 'Evaluated');
export const Assessed = createStubIcon('📊', 'Assessed');
export const Reviewed = createStubIcon('📋', 'Reviewed');
export const Audited = createStubIcon('📋', 'Audited');
export const Tested = createStubIcon('🧪', 'Tested');
export const Experimented = createStubIcon('🧪', 'Experimented');
export const Researched = createStubIcon('🔬', 'Researched');
export const Investigated = createStubIcon('🔍', 'Investigated');
export const Explored = createStubIcon('🗺️', 'Explored');
export const Discovered = createStubIcon('🔍', 'Discovered');
export const Found = createStubIcon('🔍', 'Found');
export const Located = createStubIcon('📍', 'Located');
export const Positioned = createStubIcon('📍', 'Positioned');
export const Placed = createStubIcon('📍', 'Placed');
export const Installed = createStubIcon('🔧', 'Installed');
export const Mounted = createStubIcon('🔧', 'Mounted');
export const Attached = createStubIcon('📎', 'Attached');
export const Connected = createStubIcon('🔗', 'Connected');
export const Linked = createStubIcon('🔗', 'Linked');
export const Joined = createStubIcon('🔗', 'Joined');
export const United = createStubIcon('🤝', 'United');
export const Combined = createStubIcon('🤝', 'Combined');
export const Merged = createStubIcon('🤝', 'Merged');
export const Integrated = createStubIcon('🤝', 'Integrated');
export const Synchronized = createStubIcon('🔄', 'Synchronized');
export const Coordinated = createStubIcon('🤝', 'Coordinated');
export const Organized = createStubIcon('📋', 'Organized');
export const Structured = createStubIcon('🏗️', 'Structured');
export const Formatted = createStubIcon('📄', 'Formatted');
export const Styled = createStubIcon('🎨', 'Styled');
export const Themed = createStubIcon('🎨', 'Themed');
export const Customized = createStubIcon('⚙️', 'Customized');
export const Configured = createStubIcon('⚙️', 'Configured');
export const Setup = createStubIcon('⚙️', 'Setup');
export const Initialized = createStubIcon('🔄', 'Initialized');
export const Prepared = createStubIcon('📋', 'Prepared');
export const Ready = createStubIcon('✅', 'Ready');
export const Available = createStubIcon('✅', 'Available');
export const Online = createStubIcon('🟢', 'Online');
export const Offline = createStubIcon('🔴', 'Offline');
export const Disconnected = createStubIcon('🔴', 'Disconnected');
export const Active = createStubIcon('🟢', 'Active');
export const Inactive = createStubIcon('🔴', 'Inactive');
export const Disabled = createStubIcon('🔴', 'Disabled');
export const On = createStubIcon('🟢', 'On');
export const Off = createStubIcon('🔴', 'Off');
export const Up = createStubIcon('⬆️', 'Up');
export const Down = createStubIcon('⬇️', 'Down');
export const Left = createStubIcon('⬅️', 'Left');
export const Right = createStubIcon('➡️', 'Right');
export const Forward = createStubIcon('⏩', 'Forward');
export const Backward = createStubIcon('⏪', 'Backward');
export const Next = createStubIcon('⏭️', 'Next');
export const Previous = createStubIcon('⏮️', 'Previous');
export const First = createStubIcon('⏮️', 'First');
export const Last = createStubIcon('⏭️', 'Last');
export const Begin = createStubIcon('▶️', 'Begin');
export const End = createStubIcon('⏹️', 'End');
export const Start = createStubIcon('▶️', 'Start');
export const Finish = createStubIcon('⏹️', 'Finish');
export const Continue = createStubIcon('▶️', 'Continue');
export const Resume = createStubIcon('▶️', 'Resume');
export const Reset = createStubIcon('🔄', 'Reset');
export const Restart = createStubIcon('🔄', 'Restart');
export const Reload = createStubIcon('🔄', 'Reload');
export const Refresh = createStubIcon('🔄', 'Refresh');
export const Update = createStubIcon('🔄', 'Update');
export const Upgrade = createStubIcon('⬆️', 'Upgrade');
export const Downgrade = createStubIcon('⬇️', 'Downgrade');
export const Install = createStubIcon('📥', 'Install');
export const Uninstall = createStubIcon('🗑️', 'Uninstall');
export const Remove = createStubIcon('🗑️', 'Remove');
export const Delete = createStubIcon('🗑️', 'Delete');
export const Erase = createStubIcon('🗑️', 'Erase');
export const Clear = createStubIcon('🧹', 'Clear');
export const Clean = createStubIcon('🧹', 'Clean');
export const Wipe = createStubIcon('🧹', 'Wipe');
export const Purge = createStubIcon('🧹', 'Purge');
export const Flush = createStubIcon('🚽', 'Flush');
export const Empty = createStubIcon('🗑️', 'Empty');
export const Void = createStubIcon('⚫', 'Void');
export const Null = createStubIcon('⚫', 'Null');
export const None = createStubIcon('⚫', 'None');

// Catch-all export function for any missing icons
export const createMissingIcon = (name: string) => createStubIcon('❓', name);

// Create a Proxy to handle any missing icon exports dynamically
const iconProxy = new Proxy({}, {
  get(target: any, prop: string) {
    // If the icon exists in our exports, return it
    if (prop in target) {
      return target[prop];
    }

    // For any missing icon, create a safe stub on the fly
    console.warn(`Missing Lucide icon: ${prop}, using fallback`);
    return createStubIcon('❓', prop);
  }
});

// SAFE: Add only properly defined icons to the proxy target
// This prevents Object.assign errors from undefined variables
try {
  const definedIcons = {
    AlertTriangle, AlertCircle, Activity, Award, ArrowLeft, ArrowRight, ArrowUp, ArrowDown,
    BarChart3, Bell, BellOff, Briefcase, Calendar, Camera, Check, CheckCircle,
    ChevronDown, ChevronUp, ChevronLeft, ChevronRight, Circle, Clock, Cloud, Construction,
    Copy, CreditCard, Crown, Database, Diamond, DollarSign, Dot, Download, Edit,
    ExternalLink, Eye, EyeOff, Factory, FileText, Filter, Flag, Flower, Gem, Gift,
    Globe, GripVertical, HardHat, Hash, Heart, Hexagon, History, Home, Image, Key,
    Leaf, Link, Loader2, Lock, Mail, MapPin, Medal, Menu, MessageCircle, MessageSquare,
    Minus, Moon, Octagon, Package, Palette, PanelLeft, Percent, Phone, Pickaxe, Plus,
    RefreshCw, Reply, Search, Send, Settings, Shield, ShieldAlert, ShieldCheck, ShieldX,
    ShoppingCart, Square, Star, Sun, Target, Trash2, TrendingUp, Triangle, Trophy,
    Truck, Unlock, Upload, User, UserCheck, UserX, Users, Video, Wallet, X, XCircle,
    Zap, Calculator, Bug, Code, Terminal, Cpu, HardDrive, Monitor, Smartphone, Tablet
  };

  // Filter out any undefined values before assigning
  Object.keys(definedIcons).forEach(key => {
    if (definedIcons[key] !== undefined) {
      iconProxy[key] = definedIcons[key];
    }
  });

  console.log('✅ LucideStub: Successfully loaded', Object.keys(definedIcons).length, 'icon stubs');
} catch (error) {
  console.error('❌ LucideStub: Error loading icons:', error);
}

// Export the proxy as default to handle any missing icons
export default iconProxy;