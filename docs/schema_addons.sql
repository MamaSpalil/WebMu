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
