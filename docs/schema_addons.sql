/* =====================================================================
 *  WebMu — schema add-ons for MuOnline (Season 3 Episode 1)
 *
 *  This script is IDEMPOTENT — run it as many times as you like.  It
 *  augments the stock MuOnline schema (as shipped in MuOnline_Bak) with
 *  the optional columns and tables that WebMu's site modules expect:
 *
 *    - MEMB_INFO.credits         -- main donate currency (cr_*)
 *    - MEMB_INFO.usd             -- secondary donate currency (usd_*)
 *    - MEMB_INFO.cash            -- vote-reward currency (gr_*)
 *    - GameShopPoint             -- in-game shop WCoin balance (wcoin_*)
 *    - WebDonateItems            -- catalog of donate-shop items
 *    - WebDonateLog              -- purchase log for in-game fulfillment
 *    - WebVotePoints             -- fallback table when MEMB_INFO.cash
 *                                   cannot be added (managed accounts DB)
 *
 *  After RESTORE DATABASE MuOnline FROM DISK = N'MuOnline_Bak' run:
 *      USE [MuOnline];
 *      :r schema_addons.sql
 *
 *  Or open this file in SSMS against the MuOnline database and Execute.
 * ===================================================================== */

SET NOCOUNT ON;
USE [MuOnline];
GO

/* ---- Currency columns on MEMB_INFO ---------------------------------- */
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_NAME = 'MEMB_INFO' AND COLUMN_NAME = 'credits')
BEGIN
    ALTER TABLE MEMB_INFO ADD credits INT NOT NULL CONSTRAINT DF_MEMB_INFO_credits DEFAULT 0;
    PRINT 'Added MEMB_INFO.credits';
END
GO

IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_NAME = 'MEMB_INFO' AND COLUMN_NAME = 'usd')
BEGIN
    ALTER TABLE MEMB_INFO ADD usd INT NOT NULL CONSTRAINT DF_MEMB_INFO_usd DEFAULT 0;
    PRINT 'Added MEMB_INFO.usd';
END
GO

IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_NAME = 'MEMB_INFO' AND COLUMN_NAME = 'cash')
BEGIN
    ALTER TABLE MEMB_INFO ADD cash INT NOT NULL CONSTRAINT DF_MEMB_INFO_cash DEFAULT 0;
    PRINT 'Added MEMB_INFO.cash';
END
GO

/* ---- GameShopPoint (WCoin storage) ---------------------------------- */
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'GameShopPoint')
BEGIN
    CREATE TABLE GameShopPoint (
        AccountID  varchar(10) NOT NULL PRIMARY KEY,
        WCoinP     int         NOT NULL DEFAULT 0,
        WCoinC     int         NOT NULL DEFAULT 0,
        GoblinP    int         NOT NULL DEFAULT 0
    );
    PRINT 'Created GameShopPoint';
END
GO

/* ---- WebDonateItems (donate-shop catalog) --------------------------- */
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'WebDonateItems')
BEGIN
    CREATE TABLE WebDonateItems (
        id              int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        name            nvarchar(80)  NOT NULL,
        image           varchar(40)   NOT NULL DEFAULT 'donate.svg',
        price_credits   int           NOT NULL DEFAULT 0,
        price_wcoin     int           NOT NULL DEFAULT 0,
        category        varchar(20)   NOT NULL DEFAULT 'wcoin'
    );
    PRINT 'Created WebDonateItems';
END
GO

/* ---- WebDonateLog (purchase log) ------------------------------------ */
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'WebDonateLog')
BEGIN
    CREATE TABLE WebDonateLog (
        id              int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        account         varchar(10)  NOT NULL,
        item_id         int          NOT NULL,
        item_name       nvarchar(80) NOT NULL,
        paid_credits    int          NOT NULL DEFAULT 0,
        paid_wcoin      int          NOT NULL DEFAULT 0,
        ip              varchar(45)  NULL,
        ts              datetime     NOT NULL DEFAULT GETDATE(),
        delivered       bit          NOT NULL DEFAULT 0
    );
    CREATE INDEX IX_WebDonateLog_account ON WebDonateLog(account);
    CREATE INDEX IX_WebDonateLog_ts ON WebDonateLog(ts);
    PRINT 'Created WebDonateLog';
END
GO

/* ---- webmu_log (per-account action log) ----------------------------- *
 *  Used by the site to record security- and finance-relevant events:
 *    login_ok / login_fail, register, change_password,
 *    vote, buy, market_list / market_buy, etc.
 *  Optional: every call site is gated by db_table_exists("webmu_log"),
 *  so the site keeps working even if this table was never created.
 * --------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'webmu_log')
BEGIN
    CREATE TABLE webmu_log (
        id          int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        ts          datetime      NOT NULL DEFAULT GETDATE(),
        ip          varchar(45)   NULL,
        account     varchar(10)   NULL,
        action      varchar(32)   NOT NULL,
        details     nvarchar(400) NULL
    );
    CREATE INDEX IX_webmu_log_account ON webmu_log(account);
    CREATE INDEX IX_webmu_log_action  ON webmu_log(action);
    CREATE INDEX IX_webmu_log_ts      ON webmu_log(ts);
    PRINT 'Created webmu_log';
END
GO

/* ---- WebVotePoints (fallback for vote rewards) ---------------------- */
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'WebVotePoints')
BEGIN
    CREATE TABLE WebVotePoints (
        account     varchar(10) NOT NULL PRIMARY KEY,
        points      int         NOT NULL DEFAULT 0,
        updated_at  datetime    NOT NULL DEFAULT GETDATE()
    );
    PRINT 'Created WebVotePoints';
END
GO

/* ---- Helpful indexes used by ranking / online listing --------------- */
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
     WHERE name = 'IX_Character_Resets_cLevel' AND object_id = OBJECT_ID('Character'))
BEGIN
    CREATE INDEX IX_Character_Resets_cLevel ON Character(Resets DESC, cLevel DESC);
    PRINT 'Created IX_Character_Resets_cLevel';
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
     WHERE name = 'IX_MEMB_STAT_ConnectStat' AND object_id = OBJECT_ID('MEMB_STAT'))
BEGIN
    CREATE INDEX IX_MEMB_STAT_ConnectStat ON MEMB_STAT(ConnectStat);
    PRINT 'Created IX_MEMB_STAT_ConnectStat';
END
GO

PRINT 'WebMu schema add-ons applied successfully.';
GO

/* =====================================================================
 *  Web-Vault market — items that players list for sale via the Web-
 *  Сундук "Put up for sale" form. Every listing reserves a warehouse
 *  slot (the slot is wiped while the listing is active so the same
 *  item cannot be sold twice).
 *
 *  Currency tokens stored in WebMarketItems.currency:
 *    'wcoin' | 'zen' | 'usdt'                        (numeric balances)
 *    'bless' | 'soul' | 'chaos' | 'life'             (jewels — per-item)
 *    | 'creation' | 'harmony' | 'level'
 *    | 'luck'  | 'excellent'
 *
 *  States: 'listed' | 'sold' | 'cancelled' | 'expired'.
 * ===================================================================== */
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'WebMarketItems')
BEGIN
    CREATE TABLE WebMarketItems (
        id              int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        seller_account  varchar(10)    NOT NULL,
        seller_char     nvarchar(10)   NULL,
        wh_slot         int            NULL,
        item_blob       varbinary(12)  NOT NULL,
        item_name       nvarchar(80)   NOT NULL,
        item_image      varchar(40)    NOT NULL DEFAULT '',
        item_level      int            NOT NULL DEFAULT 0,
        item_exc        int            NOT NULL DEFAULT 0,
        item_luck       bit            NOT NULL DEFAULT 0,
        item_skill      bit            NOT NULL DEFAULT 0,
        item_opt        int            NOT NULL DEFAULT 0,
        qty             int            NOT NULL DEFAULT 1,
        currency        varchar(16)    NOT NULL,
        price           decimal(20,4)  NOT NULL,
        state           varchar(16)    NOT NULL DEFAULT 'listed',
        listed_at       datetime       NOT NULL DEFAULT GETDATE(),
        sold_at         datetime       NULL,
        buyer_account   varchar(10)    NULL
    );
    CREATE INDEX IX_WebMarketItems_state    ON WebMarketItems(state);
    CREATE INDEX IX_WebMarketItems_seller   ON WebMarketItems(seller_account);
    CREATE INDEX IX_WebMarketItems_currency ON WebMarketItems(currency);
    PRINT 'Created WebMarketItems';
END
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'WebMarketLog')
BEGIN
    CREATE TABLE WebMarketLog (
        id          int IDENTITY(1,1) NOT NULL PRIMARY KEY,
        ts          datetime      NOT NULL DEFAULT GETDATE(),
        action      varchar(16)   NOT NULL,        -- list | cancel | buy | admin_cancel
        listing_id  int           NULL,
        account     varchar(10)   NULL,
        details     nvarchar(400) NULL
    );
    CREATE INDEX IX_WebMarketLog_listing ON WebMarketLog(listing_id);
    CREATE INDEX IX_WebMarketLog_account ON WebMarketLog(account);
    PRINT 'Created WebMarketLog';
END
GO

/* ---- USDT internal balance on MEMB_INFO ------------------------------ *
 *  Used as the `usdt` market currency. WebMu does NOT collect crypto on
 *  its own — server admins credit the column manually (or via a payment
 *  gateway integration that is out of scope for the website itself).
 * --------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_NAME = 'MEMB_INFO' AND COLUMN_NAME = 'usdt')
BEGIN
    ALTER TABLE MEMB_INFO ADD usdt decimal(20,4) NOT NULL CONSTRAINT DF_MEMB_INFO_usdt DEFAULT 0;
    PRINT 'Added MEMB_INFO.usdt';
END
GO

/* ---- Optional MEMB_INFO.is_admin (admin panel) ---------------------- *
 *  The admin panel primarily uses the $config["admin_accounts"]
 *  whitelist; this column is an optional alternative for installations
 *  that prefer to manage admins inside the game DB.
 * --------------------------------------------------------------------- */
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_NAME = 'MEMB_INFO' AND COLUMN_NAME = 'is_admin')
BEGIN
    ALTER TABLE MEMB_INFO ADD is_admin tinyint NOT NULL CONSTRAINT DF_MEMB_INFO_is_admin DEFAULT 0;
    PRINT 'Added MEMB_INFO.is_admin';
END
GO

/* ---- Online-hours bank + VipList (Stage 3) -------------------------- *
 *  WebOnlineHours accumulates a per-account total of in-game minutes
 *  (rolled up to integer hours when the player exchanges them).
 *  VipList is the table the GameServer reads to apply VIP perks
 *  (exp/drop/chaos/JoS bonuses) — adjust column names in opt.php
 *  ($config["vip_*"]) if your emulator uses different names.
 * --------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'WebOnlineHours')
BEGIN
    CREATE TABLE WebOnlineHours (
        account            varchar(10) NOT NULL PRIMARY KEY,
        seconds_total      int         NOT NULL DEFAULT 0,
        seconds_spent      int         NOT NULL DEFAULT 0,
        last_seen_online   datetime    NULL,
        updated_at         datetime    NOT NULL DEFAULT GETDATE()
    );
    PRINT 'Created WebOnlineHours';
END
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'VipList')
BEGIN
    CREATE TABLE VipList (
        AccountID   varchar(10)  NOT NULL PRIMARY KEY,
        VipType     tinyint      NOT NULL DEFAULT 0,
        ExpireDate  datetime     NOT NULL DEFAULT GETDATE(),
        GrantedBy   varchar(32)  NULL
    );
    CREATE INDEX IX_VipList_Expire ON VipList(ExpireDate);
    PRINT 'Created VipList';
END
GO
